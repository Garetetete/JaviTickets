<?php

namespace App\DTOs;

/**
 * Notificación de pago entrante (flujo automático).
 */
final class WebhookPaymentData
{
    /**
     * @param  array<string, mixed>  $payload  cuerpo crudo recibido
     */
    public function __construct(
        public readonly string $externalEventId,
        public readonly string $externalReference,
        public readonly string $status,
        public readonly int $apiClientId,
        public readonly array $payload = [],
        public readonly bool $signatureValid = false,
        public readonly ?float $amount = null,
    ) {}
}
