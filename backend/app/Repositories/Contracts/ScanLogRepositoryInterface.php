<?php

namespace App\Repositories\Contracts;

use App\Models\ScanLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ScanLogRepositoryInterface
{
    /**
     * Registra un intento de escaneo (append-only).
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ScanLog;

    /**
     * Último log del code, usado para la ventana anti-rebote en validación.
     */
    public function findLastForCode(string $code): ?ScanLog;

    /**
     * Listado paginado de escaneos de un evento con filtros.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginateByEvent(int $eventId, array $filters, int $perPage = 20): LengthAwarePaginator;
}
