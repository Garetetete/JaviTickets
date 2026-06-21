<?php

namespace App\Repositories\Contracts;

use App\Models\WebhookEvent;

interface WebhookEventRepositoryInterface
{
    /**
     * Idempotencia: indica si ya se recibió este evento externo de pago.
     */
    public function existsByExternalEventId(string $id): bool;

    /**
     * Registra el webhook recibido (append-only).
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): WebhookEvent;

    /**
     * Marca el webhook como ya procesado.
     */
    public function markProcessed(int $id): void;
}
