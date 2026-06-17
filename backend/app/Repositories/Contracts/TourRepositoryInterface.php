<?php

namespace App\Repositories\Contracts;

use App\Models\Tour;
use Illuminate\Support\Collection;

interface TourRepositoryInterface extends RepositoryInterface
{
    public function findBySlug(string $slug): ?Tour;

    /** @return Collection<int, Tour> */
    public function allActive(): Collection;
}
