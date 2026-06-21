<?php

namespace App\Repositories\Eloquent;

use App\Models\Ticket;
use App\Models\TicketType;
use App\Repositories\Contracts\TicketTypeRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Implementación Eloquent de {@see \App\Repositories\Contracts\TicketTypeRepositoryInterface}.
 * Es la ÚNICA capa autorizada a ejecutar consultas Eloquent sobre el modelo TicketType.
 */
class EloquentTicketTypeRepository extends EloquentRepository implements TicketTypeRepositoryInterface
{
    /** Modelo Eloquent gestionado por este repositorio. */
    protected string $model = TicketType::class;

    /**
     * {@inheritDoc}
     *
     * Consulta el primer TicketType que coincida en tour_id y slug.
     */
    public function findBySlug(int $tourId, string $slug): ?TicketType
    {
        return TicketType::query()
            ->where('tour_id', $tourId)
            ->where('slug', $slug)
            ->first();
    }

    /**
     * {@inheritDoc}
     *
     * Filtra por tour_id y scope active(), ordenados por el campo order.
     */
    public function allActiveByTour(int $tourId): Collection
    {
        return TicketType::query()
            ->where('tour_id', $tourId)
            ->active()
            ->orderBy('order')
            ->get();
    }

    /**
     * {@inheritDoc}
     *
     * Cuenta los tickets de este tipo en estados que consumen cupo
     * (issued, active, used) para validar la quota.
     */
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
