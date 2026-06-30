<?php

namespace App\DTOs;

/**
 * DTO inmutable con una notificación de pago entrante (flujo automático por
 * webhook). Lo construye el Controller tras verificar la firma HMAC y lo
 * consume {@see \App\Services\PaymentService::handleWebhook()}, que es
 * idempotente por $externalEventId.
 */
final class WebhookPaymentData
{
    /**
     * @param  string  $externalEventId  Id único del evento de pago externo (clave de idempotencia).
     * @param  string  $externalReference  Referencia de la orden (id WP) a la que aplica el pago.
     * @param  string  $status  Estado reportado por el origen (p. ej. "paid").
     * @param  int  $apiClientId  Id del api_client (tienda) que emite el webhook.
     * @param  array<string, mixed>  $payload  Cuerpo crudo recibido, almacenado para auditoría.
     * @param  bool  $signatureValid  True si el middleware ya verificó la firma HMAC.
     * @param  float|null  $amount  Monto informado por el webhook (se valida server-side).
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
