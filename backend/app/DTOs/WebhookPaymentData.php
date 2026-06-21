<?php

namespace App\DTOs;

/**
 * DTO inmutable con una notificación de pago entrante (flujo automático por
 * webhook). Lo construye el Controller tras verificar la firma HMAC y lo
 * consume {@see \App\Services\PaymentService::handleWebhook()}, que es
 * idempotente por $externalEventId.
 */
final readonly class WebhookPaymentData
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
        public string $externalEventId,
        public string $externalReference,
        public string $status,
        public int $apiClientId,
        public array $payload = [],
        public bool $signatureValid = false,
        public ?float $amount = null,
    ) {}
}
