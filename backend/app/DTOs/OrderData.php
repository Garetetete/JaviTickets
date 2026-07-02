<?php

namespace App\DTOs;

/**
 * Objeto de transferencia (DTO) inmutable con los datos necesarios para
 * registrar una orden de compra recibida desde la tienda. Desacopla el
 * Request HTTP del Service: el Controller construye este DTO y se lo pasa a
 * {@see \App\Services\PaymentService::createOrder()}.
 */
final class OrderData
{
    /**
     * @param  array<string, mixed>  $customer  Campos del comprador (first_name, last_name, document_number, email, …).
     * @param  int  $eventId  Id del evento al que pertenece la compra.
     * @param  int  $ticketTypeId  Id del tipo de ticket comprado.
     * @param  int  $quantity  Cantidad de tickets a emitir (1..50).
     * @param  float  $amount  Monto enviado por la tienda; se valida server-side contra price*quantity.
     * @param  string  $currency  Código ISO de 3 letras de la moneda (por defecto USD).
     * @param  string|null  $externalReference  Referencia externa (id de orden WP) para idempotencia/reconciliación.
     * @param  string|null  $paymentMethod  Método de pago declarado (p. ej. "transfer").
     * @param  int|null  $apiClientId  Id del api_client (tienda) que origina la orden.
     * @param  array<int, array{section?:string, seat?:string}>  $seats  Asignación de asiento por ticket (eventos numerados; opcional).
     */
    public function __construct(
        public readonly array $customer,
        public readonly int $eventId,
        public readonly int $ticketTypeId,
        public readonly int $quantity,
        public readonly float $amount,
        public readonly string $currency = 'USD',
        public readonly ?string $externalReference = null,
        public readonly ?string $paymentMethod = null,
        public readonly ?int $apiClientId = null,
        public readonly array $seats = [],
    ) {}

    /**
     * Construye el DTO desde un array asociativo (p. ej. los datos validados
     * de un Form Request), normalizando tipos y aplicando valores por defecto.
     *
     * @param  array<string, mixed>  $data  Payload con claves snake_case.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            customer: $data['customer'] ?? [],
            eventId: (int) $data['event_id'],
            ticketTypeId: (int) $data['ticket_type_id'],
            quantity: (int) ($data['quantity'] ?? 1),
            amount: (float) $data['amount'],
            currency: $data['currency'] ?? 'USD',
            externalReference: $data['external_reference'] ?? null,
            paymentMethod: $data['payment_method'] ?? null,
            apiClientId: isset($data['api_client_id']) ? (int) $data['api_client_id'] : null,
            seats: $data['seats'] ?? [],
        );
    }
}
