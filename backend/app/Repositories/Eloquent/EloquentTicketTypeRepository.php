<?php

namespace App\Repositories\Eloquent;

use App\Models\Ticket;
use App\Models\TicketType;
use App\Repositories\Contracts\TicketTypeRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentTicketTypeRepository extends EloquentRepository implements TicketTypeRepositoryInterface
{
    protected string $model = TicketType::class;

    public function findBySlug(int $tourId, string $slug): ?TicketType
    {
        return TicketType::query()
            ->where('tour_id', $tourId)
            ->where('slug', $slug)
            ->first();
    }

    public function allActiveByTour(int $tourId): Collection
    {
        return TicketType::query()
            ->where('tour_id', $tourId)
            ->active()
            ->orderBy('order')
            ->get();
    }

    public function countIssuedByType(int $ticketTypeId): int
    {
        return Ticket::query()
            ->where('ticket_type_id', $ticketTypeId)
            ->whereIn('status', [
                Ticket::STATUS_ISSUED,
                Ticket::STATUS_ACTIVE,
                Ticket::STATUS_USED,
            ])
            ->count();
    }
}
