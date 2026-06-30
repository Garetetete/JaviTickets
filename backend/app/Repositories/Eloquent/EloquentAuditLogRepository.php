<?php

namespace App\Repositories\Eloquent;

use App\Models\AuditLog;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Implementación Eloquent de {@see \App\Repositories\Contracts\AuditLogRepositoryInterface}.
 * Es la ÚNICA capa autorizada a ejecutar consultas Eloquent sobre el modelo AuditLog.
 */
class EloquentAuditLogRepository implements AuditLogRepositoryInterface
{
    /**
     * {@inheritDoc}
     *
     * Inserta una entrada de auditoría (append-only) por asignación masiva.
     */
    public function create(array $data): AuditLog
    {
        return AuditLog::query()->create($data);
    }

    /**
     * {@inheritDoc}
     *
     * Devuelve la auditoría paginada ordenada por created_at descendente.
     */
    public function paginate(int $perPage = 20): LengthAwarePaginator
    {
        return AuditLog::query()->latest('created_at')->paginate($perPage);
    }
}
