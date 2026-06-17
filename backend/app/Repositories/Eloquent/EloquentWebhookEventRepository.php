<?php

namespace App\Repositories\Eloquent;

use App\Models\WebhookEvent;
use App\Repositories\Contracts\WebhookEventRepositoryInterface;

class EloquentWebhookEventRepository implements WebhookEventRepositoryInterface
{
    public function existsByExternalEventId(string $id): bool
    {
        return WebhookEvent::query()->where('external_event_id', $id)->exists();
    }

    public function create(array $data): WebhookEvent
    {
        return WebhookEvent::query()->create($data);
    }

    public function markProcessed(int $id): void
    {
        WebhookEvent::query()->whereKey($id)->update(['processed' => true]);
    }
}
