<?php

namespace App\DTOs;

/**
 * Notificación de pago entrante (flujo automático).
 */
final readonly class WebhookPaymentData
{
    /**
     * @param  array<string, mixed>  $payload  cuerpo crudo recibido
     */
    public function __construct(
        public string $externalEventId,
        public string $externalReference,
        public string $status,
        public int $apiClientId,
        public array $payload = [],
        public bool $signatureValid = false,
        public ?float $amount = null,
    ) {}
}
