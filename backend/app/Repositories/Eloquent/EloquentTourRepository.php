<?php

namespace App\Repositories\Eloquent;

use App\Models\Tour;
use App\Repositories\Contracts\TourRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Implementación Eloquent de {@see \App\Repositories\Contracts\TourRepositoryInterface}.
 * Es la ÚNICA capa autorizada a ejecutar consultas Eloquent sobre el modelo Tour.
 */
class EloquentTourRepository extends EloquentRepository implements TourRepositoryInterface
{
    /** Modelo Eloquent gestionado por este repositorio. */
    protected string $model = Tour::class;

    /**
     * {@inheritDoc}
     *
     * Consulta el primer Tour cuyo slug coincida.
     */
    public function findBySlug(string $slug): ?Tour
    {
        return Tour::query()->where('slug', $slug)->first();
    }

    /**
     * {@inheritDoc}
     *
     * Aplica el scope active() y ordena por nombre.
     */
    public function allActive(): Collection
    {
        return Tour::query()->active()->orderBy('name')->get();
    }
}
