<?php

namespace App\Repositories\Contracts;

use App\Models\Event;
use Illuminate\Support\Collection;

interface EventRepositoryInterface extends RepositoryInterface
{
    /**
     * Busca un evento por su slug dentro de un tour. Null si no existe.
     */
    public function findBySlug(int $tourId, string $slug): ?Event;

    /**
     * Todos los eventos activos de un tour.
     *
     * @return Collection<int, Event>
     */
    public function allActiveByTour(int $tourId): Collection;

    /**
     * Cuenta los tickets que consumen aforo (issued|active|used) del evento.
     */
    public function countIssuedTickets(int $eventId): int;

    /**
     * SELECT ... FOR UPDATE del evento — debe invocarse dentro de una
     * transacción. Serializa la emisión para validar el aforo de forma atómica.
     */
    public function lockForIssue(int $eventId): Event;
}
