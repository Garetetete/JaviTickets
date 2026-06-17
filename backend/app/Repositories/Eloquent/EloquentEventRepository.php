<?php

namespace App\Repositories\Eloquent;

use App\Models\Event;
use App\Models\Ticket;
use App\Repositories\Contracts\EventRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentEventRepository extends EloquentRepository implements EventRepositoryInterface
{
    protected string $model = Event::class;

    public function findBySlug(int $tourId, string $slug): ?Event
    {
        return Event::query()
            ->where('tour_id', $tourId)
            ->where('slug', $slug)
            ->first();
    }

    public function allActiveByTour(int $tourId): Collection
    {
        return Event::query()
            ->where('tour_id', $tourId)
            ->active()
            ->orderBy('event_date')
            ->get();
    }

    public function countIssuedTickets(int $eventId): int
    {
        return Ticket::query()
            ->where('event_id', $eventId)
            ->whereIn('status', [
                Ticket::STATUS_ISSUED,
                Ticket::STATUS_ACTIVE,
                Ticket::STATUS_USED,
            ])
            ->count();
    }

    public function lockForIssue(int $eventId): Event
    {
        return Event::query()
            ->whereKey($eventId)
            ->lockForUpdate()
            ->firstOrFail();
    }
}
