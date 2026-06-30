<?php

namespace App\DTOs;

/**
 * Datos para registrar una orden de compra (desde la tienda).
 */
final class OrderData
{
    /**
     * @param  array<string, mixed>  $customer  campos del comprador
     * @param  array<int, array{section?:string, seat?:string}>  $seats  asignación por ticket (opcional)
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
     * @param  array<string, mixed>  $data
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
