<?php

namespace App\Repositories\Eloquent;

use App\Models\Ticket;
use App\Repositories\Contracts\MetricsRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EloquentMetricsRepository implements MetricsRepositoryInterface
{
    private const OCCUPYING = [
        Ticket::STATUS_ISSUED,
        Ticket::STATUS_ACTIVE,
        Ticket::STATUS_USED,
    ];

    public function issuedCount(int $eventId): int
    {
        return Ticket::query()
            ->where('event_id', $eventId)
            ->whereIn('status', self::OCCUPYING)
            ->count();
    }

    public function usedCount(int $eventId): int
    {
        return Ticket::query()
            ->where('event_id', $eventId)
            ->where('status', Ticket::STATUS_USED)
            ->count();
    }

    public function salesByType(int $eventId): Collection
    {
        return DB::table('tickets')
            ->join('ticket_types', 'tickets.ticket_type_id', '=', 'ticket_types.id')
            ->whereNull('tickets.deleted_at')
            ->where('tickets.event_id', $eventId)
            ->whereIn('tickets.status', self::OCCUPYING)
            ->groupBy('ticket_types.id', 'ticket_types.name', 'ticket_types.price')
            ->select([
                'ticket_types.id as ticket_type_id',
                'ticket_types.name',
                DB::raw('count(*) as issued'),
                DB::raw("sum(case when tickets.status = 'used' then 1 else 0 end) as used"),
                'ticket_types.price',
            ])
            ->get()
            ->map(fn ($row) => (object) [
                'ticket_type_id' => (int) $row->ticket_type_id,
                'name' => $row->name,
                'issued' => (int) $row->issued,
                'used' => (int) $row->used,
                'revenue' => (float) $row->issued * (float) $row->price,
            ]);
    }

    public function scanResults(int $eventId): array
    {
        return DB::table('scan_logs')
            ->where('event_id', $eventId)
            ->groupBy('result')
            ->select('result', DB::raw('count(*) as total'))
            ->pluck('total', 'result')
            ->map(fn ($v) => (int) $v)
            ->all();
    }
}
