<?php

namespace App\Repositories\Contracts;

use App\Models\ScanLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ScanLogRepositoryInterface
{
    public function create(array $data): ScanLog;

    /** Último log del code (ventana anti-rebote en validación). */
    public function findLastForCode(string $code): ?ScanLog;

    public function paginateByEvent(int $eventId, array $filters, int $perPage = 20): LengthAwarePaginator;
}
