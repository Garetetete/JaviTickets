<?php

namespace App\Repositories\Contracts;

use App\Models\Tour;
use Illuminate\Support\Collection;

interface TourRepositoryInterface extends RepositoryInterface
{
    /**
     * Busca un tour por su slug. Null si no existe.
     */
    public function findBySlug(string $slug): ?Tour;

    /**
     * Todos los tours activos.
     *
     * @return Collection<int, Tour>
     */
    public function allActive(): Collection;
}
