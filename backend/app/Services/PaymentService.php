<?php

namespace App\Services;

use App\DTOs\OrderData;
use App\DTOs\ReceiptData;
use App\DTOs\WebhookPaymentData;
use App\Exceptions\InvalidOrderException;
use App\Exceptions\InvalidPaymentStateException;
use App\Exceptions\OrderAlreadyVerifiedException;
use App\Exceptions\OrderNotFoundException;
use App\Models\Order;
use App\Models\Ticket;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use App\Repositories\Contracts\EventRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\PaymentReceiptRepositoryInterface;
use App\Repositories\Contracts\TicketTypeRepositoryInterface;
use App\Repositories\Contracts\WebhookEventRepositoryInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;

/**
 * Registra órdenes y gestiona los dos flujos de pago:
 *  - manual:   subir desprendible -> verificación admin -> emisión
 *  - automático: webhook firmado e idempotente -> emisión
 * Los tickets solo se emiten cuando el pago queda `verified`.
 */
class PaymentService
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly CustomerRepositoryInterface $customers,
        private readonly PaymentReceiptRepositoryInterface $receipts,
        private readonly WebhookEventRepositoryInterface $webhooks,
        private readonly TicketIssuanceService $issuance,
        private readonly EventRepositoryInterface $events,
        private readonly TicketTypeRepositoryInterface $ticketTypes,
    ) {}

    /**
     * Crea una orden en `pending_payment`. Idempotente por external_reference.
     */
    public function createOrder(OrderData $data): Order
    {
        if ($data->externalReference !== null) {
            $existing = $this->orders->findByExternalReference($data->externalReference);
            if ($existing !== null) {
                return $existing;
            }
        }

        $expectedAmount = $this->validateOrderIntegrity($data);

        $customer = $this->customers->firstOrCreate($data->customer);

        try {
            return $this->orders->create([
                'customer_id' => $customer->id,
                'external_reference' => $data->externalReference,
                'api_client_id' => $data->apiClientId,
                'payment_status' => Order::STATUS_PENDING_PAYMENT,
                'payment_method' => $data->paymentMethod,
                'amount' => $expectedAmount, // monto autoritativo (server-side)
                'currency' => $data->currency,
                'quantity' => $data->quantity,
                'seats' => $data->seats !== [] ? $data->seats : null,
                'ticket_type_id' => $data->ticketTypeId,
                'event_id' => $data->eventId,
            ]);
        } catch (UniqueConstraintViolationException $e) {
            // Doble clic / carrera: dos POST idénticos a la vez. El índice único
            // de external_reference protege; devolvemos la orden ya creada.
            if ($data->externalReference !== null
                && ($existing = $this->orders->findByExternalReference($data->externalReference)) !== null) {
                return $existing;
            }
            throw $e;
        }
    }

    /**
     * Verifica integridad de la orden (server-side) y devuelve el monto autoritativo:
     *  - el tipo de ticket pertenece al mismo tour del evento (y al evento si es específico),
     *  - amount enviado == price * quantity (anti-manipulación),
     *  - si vienen seats, su número coincide con quantity.
     */
    private function validateOrderIntegrity(OrderData $data): float
    {
        $event = $this->events->find($data->eventId);
        $type = $this->ticketTypes->find($data->ticketTypeId);

        if ($event === null || $type === null) {
            throw new InvalidOrderException('Evento o tipo de ticket inexistente.');
        }

        if ($type->tour_id !== $event->tour_id) {
            throw new InvalidOrderException('El tipo de ticket no pertenece al tour del evento.');
        }

        if ($type->event_id !== null && $type->event_id !== $event->id) {
            throw new InvalidOrderException('El tipo de ticket no aplica a este evento.');
        }

        if (strtoupper($data->currency) !== strtoupper($type->currency)) {
            throw new InvalidOrderException(
                "La moneda ({$data->currency}) no coincide con la del tipo de ticket ({$type->currency})."
            );
        }

        $expected = round((float) $type->price * $data->quantity, 2);
        if (abs($expected - round($data->amount, 2)) > 0.001) {
            throw new InvalidOrderException(
                "El monto no coincide con el precio del tipo de ticket (esperado {$expected})."
            );
        }

        if ($data->seats !== []) {
            if (! $event->isSeated()) {
                throw new InvalidOrderException('Este evento es de admisión general; no admite selección de asientos.');
            }
            if (count($data->seats) !== $data->quantity) {
                throw new InvalidOrderException('El número de asientos no coincide con la cantidad.');
            }
        }

        return $expected;
    }

    /**
     * Adjunta un desprendible (flujo manual) y pasa a `pending_verification`.
     */
    public function attachReceipt(int $orderId, ReceiptData $data): Order
    {
        $order = $this->findOrFail($orderId);

        if (in_array($order->payment_status, [Order::STATUS_VERIFIED, Order::STATUS_REJECTED], true)) {
            throw new InvalidPaymentStateException;
        }

        $this->receipts->create([
            'order_id' => $order->id,
            'file_path' => $data->filePath,
            'original_name' => $data->originalName,
            'mime_type' => $data->mimeType,
            'uploaded_by' => $data->uploadedBy,
        ]);

        return $this->orders->update($order->id, [
            'payment_status' => Order::STATUS_PENDING_VERIFICATION,
        ]);
    }

    /**
     * Verificación manual (admin): marca verificado y emite tickets.
     *
     * @return Collection<int, Ticket>
     */
    public function verifyManually(int $orderId, int $adminUserId): Collection
    {
        $order = $this->findOrFail($orderId);

        if ($order->payment_status === Order::STATUS_VERIFIED) {
            throw new OrderAlreadyVerifiedException;
        }
        if ($order->payment_status === Order::STATUS_REJECTED) {
            throw new InvalidPaymentStateException('La orden fue rechazada y no puede verificarse.');
        }

        $order = $this->orders->markVerified($order->id, $adminUserId);

        return $this->issuance->issueForOrder($order);
    }

    /**
     * Rechaza manualmente el pago de una orden (registra el motivo). No puede
     * rechazarse una orden ya verificada.
     */
    public function rejectManually(int $orderId, string $reason): Order
    {
        $order = $this->findOrFail($orderId);

        if ($order->payment_status === Order::STATUS_VERIFIED) {
            throw new InvalidPaymentStateException('No se puede rechazar una orden ya verificada.');
        }

        return $this->orders->markRejected($order->id, $reason);
    }

    /**
     * Webhook de pago (flujo automático). Idempotente por external_event_id.
     *
     * @return Collection<int, Ticket>
     */
    public function handleWebhook(WebhookPaymentData $data): Collection
    {
        // Idempotencia: si ya lo procesamos, devolvemos los tickets existentes.
        if ($this->webhooks->existsByExternalEventId($data->externalEventId)) {
            $order = $this->orders->findByExternalReference($data->externalReference);

            return $order ? $order->tickets()->get() : collect();
        }

        $webhook = $this->webhooks->create([
            'api_client_id' => $data->apiClientId,
            'external_event_id' => $data->externalEventId,
            'order_external_reference' => $data->externalReference,
            'payload' => $data->payload,
            'signature_valid' => $data->signatureValid,
            'processed' => false,
        ]);

        $order = $this->orders->findByExternalReference($data->externalReference);
        if ($order === null) {
            throw new OrderNotFoundException(
                "No existe orden con referencia {$data->externalReference} para el webhook."
            );
        }

        if ($order->payment_status !== Order::STATUS_VERIFIED) {
            $order = $this->orders->markVerified($order->id, null);
        }

        $tickets = $this->issuance->issueForOrder($order);

        $this->webhooks->markProcessed($webhook->id);

        return $tickets;
    }

    /**
     * Busca una orden por id o lanza OrderNotFoundException si no existe.
     */
    private function findOrFail(int $orderId): Order
    {
        $order = $this->orders->find($orderId);

        if ($order === null) {
            throw new OrderNotFoundException;
        }

        return $order;
    }
}
