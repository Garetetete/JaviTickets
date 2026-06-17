<?php

namespace App\Services;

use App\Exceptions\CapacityExceededException;
use App\Exceptions\OrderNotVerifiedException;
use App\Models\Order;
use App\Models\Ticket;
use App\Repositories\Contracts\EventRepositoryInterface;
use App\Repositories\Contracts\TicketRepositoryInterface;
use App\Repositories\Contracts\TicketTypeRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Emite los tickets de una orden verificada, validando el aforo de forma
 * atómica (lock del evento dentro de una transacción) para que nunca se
 * emita por encima de la capacidad ni del cupo por tipo.
 */
class TicketIssuanceService
{
    public function __construct(
        private readonly EventRepositoryInterface $events,
        private readonly TicketTypeRepositoryInterface $ticketTypes,
        private readonly TicketRepositoryInterface $tickets,
        private readonly QrService $qr,
    ) {}

    /**
     * @return Collection<int, Ticket>
     */
    public function issueForOrder(Order $order): Collection
    {
        if (! $order->isVerified()) {
            throw new OrderNotVerifiedException;
        }

        // Idempotencia: si ya tiene tickets, no re-emite.
        $alreadyIssued = $order->tickets()->get();
        if ($alreadyIssued->isNotEmpty()) {
            return $alreadyIssued;
        }

        return DB::transaction(function () use ($order) {
            $event = $this->events->lockForIssue($order->event_id);

            $issued = $this->events->countIssuedTickets($event->id);
            if ($issued + $order->quantity > $event->capacity) {
                throw new CapacityExceededException(
                    "Aforo del evento excedido (capacidad {$event->capacity}, emitidos {$issued})."
                );
            }

            $type = $this->ticketTypes->find($order->ticket_type_id);
            if ($type && $type->quota !== null) {
                $issuedType = $this->ticketTypes->countIssuedByType($type->id);
                if ($issuedType + $order->quantity > $type->quota) {
                    throw new CapacityExceededException(
                        "Cupo del tipo '{$type->slug}' excedido (cupo {$type->quota}, emitidos {$issuedType})."
                    );
                }
            }

            $snapshot = $this->customerSnapshot($order);

            $rows = [];
            for ($i = 0; $i < $order->quantity; $i++) {
                $code = (string) Str::ulid();
                $rows[] = [
                    'code' => $code,
                    'qr_token' => $this->qr->sign($code),
                    'key_version' => (int) config('qr.current_version', 1),
                    'order_id' => $order->id,
                    'ticket_type_id' => $order->ticket_type_id,
                    'event_id' => $order->event_id,
                    'customer_id' => $order->customer_id,
                    'status' => Ticket::STATUS_ACTIVE,
                    'metadata' => $snapshot,
                ];
            }

            return $this->tickets->createMany($rows);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function customerSnapshot(Order $order): array
    {
        $customer = $order->customer;

        if (! $customer) {
            return [];
        }

        return [
            'full_name' => $customer->full_name,
            'document_number' => $customer->document_number,
            'email' => $customer->email,
        ];
    }
}
