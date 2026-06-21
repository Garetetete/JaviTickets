<?php

namespace App\Repositories\Eloquent;

use App\Models\WebhookEvent;
use App\Repositories\Contracts\WebhookEventRepositoryInterface;

/**
 * Implementación Eloquent de {@see \App\Repositories\Contracts\WebhookEventRepositoryInterface}.
 * Es la ÚNICA capa autorizada a ejecutar consultas Eloquent sobre el modelo WebhookEvent.
 */
class EloquentWebhookEventRepository implements WebhookEventRepositoryInterface
{
    /**
     * {@inheritDoc}
     *
     * Comprueba con exists() si ya existe un WebhookEvent con ese
     * external_event_id (idempotencia).
     */
    public function existsByExternalEventId(string $id): bool
    {
        return WebhookEvent::query()->where('external_event_id', $id)->exists();
    }

    /**
     * {@inheritDoc}
     *
     * Inserta el webhook recibido (append-only) por asignación masiva.
     */
    public function create(array $data): WebhookEvent
    {
        return WebhookEvent::query()->create($data);
    }

    /**
     * {@inheritDoc}
     *
     * Actualiza el campo processed a true para el webhook indicado.
     */
    public function markProcessed(int $id): void
    {
        WebhookEvent::query()->whereKey($id)->update(['processed' => true]);
    }
}
