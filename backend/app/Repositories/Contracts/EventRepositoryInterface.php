<?php

namespace App\Repositories\Contracts;

use App\Models\Event;
use Illuminate\Support\Collection;

interface EventRepositoryInterface extends RepositoryInterface
{
    public function findBySlug(int $tourId, string $slug): ?Event;

    /** @return Collection<int, Event> */
    public function allActiveByTour(int $tourId): Collection;

    /** Tickets que cuentan para el aforo (issued|active|used). */
    public function countIssuedTickets(int $eventId): int;

    /** SELECT ... FOR UPDATE — debe invocarse dentro de una transacción. */
    public function lockForIssue(int $eventId): Event;
}
