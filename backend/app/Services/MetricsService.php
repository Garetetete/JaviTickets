<?php

namespace App\Services;

use App\Exceptions\EventNotFoundException;
use App\Repositories\Contracts\EventRepositoryInterface;
use App\Repositories\Contracts\MetricsRepositoryInterface;
use App\Repositories\Contracts\TicketTypeRepositoryInterface;

/**
 * Cálculo de métricas del dashboard admin: aforo vendido vs usado, ingresos,
 * no-show y distribución de escaneos. También disponibilidad pública.
 */
class MetricsService
{
    public function __construct(
        private readonly EventRepositoryInterface $events,
        private readonly MetricsRepositoryInterface $metrics,
        private readonly TicketTypeRepositoryInterface $ticketTypes,
    ) {}

    /**
     * Disponibilidad de un evento (pública, para la tienda): aforo restante
     * global y por tipo de ticket. Sin datos sensibles.
     *
     * @return array<string, mixed>
     */
    public function availability(int $eventId): array
    {
        $event = $this->events->find($eventId);
        if ($event === null) {
            throw new EventNotFoundException;
        }

        $issued = $this->metrics->issuedCount($eventId);
        $eventAvailable = max(0, $event->capacity - $issued);

        $soldByType = $this->metrics->salesByType($eventId)
            ->keyBy('ticket_type_id')
            ->map(fn ($r) => (int) $r->issued);

        $byType = $this->ticketTypes->allActiveByTour($event->tour_id)
            ->filter(fn ($t) => $t->event_id === null || $t->event_id === $event->id)
            ->map(function ($t) use ($soldByType, $eventAvailable) {
                $sold = $soldByType->get($t->id, 0);
                $available = $t->quota === null
                    ? $eventAvailable
                    : max(0, min($t->quota - $sold, $eventAvailable));

                return [
                    'ticket_type_id' => $t->id,
                    'name' => $t->name,
                    'price' => $t->price,
                    'currency' => $t->currency,
                    'quota' => $t->quota,
                    'sold' => $sold,
                    'available' => $available,
                ];
            })
            ->values()
            ->all();

        return [
            'event_id' => $event->id,
            'event' => $event->name,
            'capacity' => $event->capacity,
            'sold' => $issued,
            'available' => $eventAvailable,
            'sold_out' => $eventAvailable === 0,
            'by_type' => $byType,
        ];
    }

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
