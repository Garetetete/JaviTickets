<?php

namespace App\Repositories\Eloquent;

use App\Models\Ticket;
use App\Repositories\Contracts\MetricsRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Implementación Eloquent de {@see \App\Repositories\Contracts\MetricsRepositoryInterface}.
 * Es la ÚNICA capa autorizada a ejecutar consultas (Eloquent/Query Builder) de reporte.
 */
class EloquentMetricsRepository implements MetricsRepositoryInterface
{
    /**
     * Estados de ticket que ocupan aforo y cuentan como vendidos.
     *
     * @var array<int, string>
     */
    private const OCCUPYING = [
        Ticket::STATUS_ISSUED,
        Ticket::STATUS_ACTIVE,
        Ticket::STATUS_USED,
    ];

    /**
     * {@inheritDoc}
     *
     * Cuenta los tickets del evento en estados que ocupan aforo.
     */
    public function issuedCount(int $eventId): int
    {
        return Ticket::query()
            ->where('event_id', $eventId)
            ->whereIn('status', self::OCCUPYING)
            ->count();
    }

    /**
     * {@inheritDoc}
     *
     * Cuenta los tickets del evento en estado used.
     */
    public function usedCount(int $eventId): int
    {
        return Ticket::query()
            ->where('event_id', $eventId)
            ->where('status', Ticket::STATUS_USED)
            ->count();
    }

    /**
     * {@inheritDoc}
     *
     * Agrupa por tipo de ticket vía Query Builder y calcula emitidos, usados e
     * ingresos (emitidos × precio).
     */
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

    /**
     * {@inheritDoc}
     *
     * Agrupa los scan_logs del evento por result y devuelve el conteo por
     * cada resultado.
     */
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
