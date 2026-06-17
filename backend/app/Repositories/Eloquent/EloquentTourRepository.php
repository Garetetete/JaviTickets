<?php

namespace App\Repositories\Eloquent;

use App\Models\Tour;
use App\Repositories\Contracts\TourRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentTourRepository extends EloquentRepository implements TourRepositoryInterface
{
    protected string $model = Tour::class;

    public function findBySlug(string $slug): ?Tour
    {
        return Tour::query()->where('slug', $slug)->first();
    }

    public function allActive(): Collection
    {
        return Tour::query()->active()->orderBy('name')->get();
    }
}
