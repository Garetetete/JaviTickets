<?php

namespace App\Repositories\Contracts;

use App\Models\TicketType;
use Illuminate\Support\Collection;

interface TicketTypeRepositoryInterface extends RepositoryInterface
{
    public function findBySlug(int $tourId, string $slug): ?TicketType;

    /** @return Collection<int, TicketType> */
    public function allActiveByTour(int $tourId): Collection;

    /** Tickets emitidos de este tipo (para el cupo `quota`). */
    public function countIssuedByType(int $ticketTypeId): int;
}
