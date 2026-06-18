<?php

namespace App\DTOs;

/**
 * Datos para registrar una orden de compra (desde la tienda).
 */
final readonly class OrderData
{
    /**
     * @param  array<string, mixed>  $customer  campos del comprador
     * @param  array<int, array{section?:string, seat?:string}>  $seats  asignación por ticket (opcional)
     */
    public function __construct(
        public array $customer,
        public int $eventId,
        public int $ticketTypeId,
        public int $quantity,
        public float $amount,
        public string $currency = 'USD',
        public ?string $externalReference = null,
        public ?string $paymentMethod = null,
        public ?int $apiClientId = null,
        public array $seats = [],
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
