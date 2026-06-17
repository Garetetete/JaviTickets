<?php

namespace App\Repositories\Contracts;

use App\Models\WebhookEvent;

interface WebhookEventRepositoryInterface
{
    /** Idempotencia: ¿ya recibimos este evento externo? */
    public function existsByExternalEventId(string $id): bool;

    public function create(array $data): WebhookEvent;

    public function markProcessed(int $id): void;
}
