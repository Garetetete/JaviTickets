<?php

namespace App\Repositories\Contracts;

use App\Models\TicketType;
use Illuminate\Support\Collection;

interface TicketTypeRepositoryInterface extends RepositoryInterface
{
    /**
     * Busca un tipo de ticket por slug dentro de un tour. Null si no existe.
     */
    public function findBySlug(int $tourId, string $slug): ?TicketType;

    /**
     * Todos los tipos de ticket activos de un tour.
     *
     * @return Collection<int, TicketType>
     */
    public function allActiveByTour(int $tourId): Collection;

    /**
     * Cuenta los tickets emitidos de este tipo (para validar el cupo `quota`).
     */
    public function countIssuedByType(int $ticketTypeId): int;
}
