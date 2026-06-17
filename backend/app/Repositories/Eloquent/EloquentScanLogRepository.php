<?php

namespace App\Repositories\Eloquent;

use App\Models\ScanLog;
use App\Repositories\Contracts\ScanLogRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentScanLogRepository implements ScanLogRepositoryInterface
{
    public function create(array $data): ScanLog
    {
        return ScanLog::query()->create($data);
    }

    public function findLastForCode(string $code): ?ScanLog
    {
        return ScanLog::query()
            ->where('code', $code)
            ->latest('created_at')
            ->first();
    }

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
