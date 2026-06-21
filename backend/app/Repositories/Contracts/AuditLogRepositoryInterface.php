<?php

namespace App\Repositories\Contracts;

use App\Models\AuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AuditLogRepositoryInterface
{
    /**
     * Registra una entrada de auditoría (append-only).
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): AuditLog;

    /**
     * Listado paginado de la auditoría, más reciente primero.
     */
    public function paginate(int $perPage = 20): LengthAwarePaginator;
}
