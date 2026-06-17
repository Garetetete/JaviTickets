<?php

namespace App\Services;

use App\Exceptions\EventNotFoundException;
use App\Repositories\Contracts\EventRepositoryInterface;
use App\Repositories\Contracts\MetricsRepositoryInterface;

/**
 * Cálculo de métricas del dashboard admin: aforo vendido vs usado, ingresos,
 * no-show y distribución de escaneos.
 */
class MetricsService
{
    public function __construct(
        private readonly EventRepositoryInterface $events,
        private readonly MetricsRepositoryInterface $metrics,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function eventOverview(int $eventId): array
    {
        $event = $this->events->find($eventId);
        if ($event === null) {
            throw new EventNotFoundException;
        }

        $issued = $this->metrics->issuedCount($eventId);
        $used = $this->metrics->usedCount($eventId);

        return [
            'event_id' => $eventId,
            'capacity' => $event->capacity,
            'issued' => $issued,
            'used' => $used,
            'available' => max(0, $event->capacity - $issued),
            'no_show_rate' => $issued > 0 ? round(($issued - $used) / $issued, 4) : 0.0,
        ];
    }

    /**
     * @return array<int, object>
     */
    public function salesByType(int $eventId): array
    {
        return $this->metrics->salesByType($eventId)->all();
    }

    public function revenue(int $eventId): float
    {
        return (float) $this->metrics->salesByType($eventId)->sum('revenue');
    }

    /**
     * @return array<string, int>
     */
    public function scanResults(int $eventId): array
    {
        return $this->metrics->scanResults($eventId);
    }
}
