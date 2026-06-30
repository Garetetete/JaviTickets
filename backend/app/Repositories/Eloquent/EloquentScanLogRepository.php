<?php

namespace App\Repositories\Eloquent;

use App\Models\ScanLog;
use App\Repositories\Contracts\ScanLogRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Implementación Eloquent de {@see \App\Repositories\Contracts\ScanLogRepositoryInterface}.
 * Es la ÚNICA capa autorizada a ejecutar consultas Eloquent sobre el modelo ScanLog.
 */
class EloquentScanLogRepository implements ScanLogRepositoryInterface
{
    /**
     * {@inheritDoc}
     *
     * Inserta el intento de escaneo (append-only) por asignación masiva.
     */
    public function create(array $data): ScanLog
    {
        return ScanLog::query()->create($data);
    }

    /**
     * {@inheritDoc}
     *
     * Devuelve el último ScanLog del code ordenado por created_at descendente.
     */
    public function findLastForCode(string $code): ?ScanLog
    {
        return ScanLog::query()
            ->where('code', $code)
            ->latest('created_at')
            ->first();
    }

    /**
     * {@inheritDoc}
     *
     * Filtra por evento y aplica condicionalmente (when) result y rango de
     * fechas, paginando por created_at descendente.
     */
    public function paginateByEvent(int $eventId, array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return ScanLog::query()
            ->where('event_id', $eventId)
            ->when($filters['result'] ?? null, fn ($q, $v) => $q->where('result', $v))
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->where('created_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->where('created_at', '<=', $v))
            ->latest('created_at')
            ->paginate($perPage);
    }
}
