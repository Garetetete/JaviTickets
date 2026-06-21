<?php

namespace App\Repositories\Eloquent;

use App\Models\Event;
use App\Models\Ticket;
use App\Repositories\Contracts\EventRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Implementación Eloquent de {@see \App\Repositories\Contracts\EventRepositoryInterface}.
 * Es la ÚNICA capa autorizada a ejecutar consultas Eloquent sobre el modelo Event.
 */
class EloquentEventRepository extends EloquentRepository implements EventRepositoryInterface
{
    /** Modelo Eloquent gestionado por este repositorio. */
    protected string $model = Event::class;

    /**
     * {@inheritDoc}
     *
     * Consulta el primer Event que coincida en tour_id y slug.
     */
    public function findBySlug(int $tourId, string $slug): ?Event
    {
        return Event::query()
            ->where('tour_id', $tourId)
            ->where('slug', $slug)
            ->first();
    }

    /**
     * {@inheritDoc}
     *
     * Filtra por tour_id y scope active(), ordenados por fecha de evento.
     */
    public function allActiveByTour(int $tourId): Collection
    {
        return Event::query()
            ->where('tour_id', $tourId)
            ->active()
            ->orderBy('event_date')
            ->get();
    }

    /**
     * {@inheritDoc}
     *
     * Cuenta los tickets del evento en estados que consumen aforo
     * (issued, active, used).
     */
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

    /**
     * {@inheritDoc}
     *
     * Bloquea la fila del evento con lockForUpdate dentro de la transacción
     * activa para serializar la validación del aforo.
     */
    public function lockForIssue(int $eventId): Event
    {
        return Event::query()
            ->whereKey($eventId)
            ->lockForUpdate()
            ->firstOrFail();
    }
}
