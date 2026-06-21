<?php

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

/**
 * Agregados de solo lectura para el dashboard. Encapsula las consultas
 * de reporte para que MetricsService no toque Eloquent directamente.
 */
interface MetricsRepositoryInterface
{
    /**
     * Tickets que ocupan aforo (issued|active|used) del evento.
     */
    public function issuedCount(int $eventId): int;

    /**
     * Tickets ya usados (asistentes que ingresaron) del evento.
     */
    public function usedCount(int $eventId): int;

    /**
     * Ventas agregadas por tipo de ticket: emitidos, usados e ingresos.
     *
     * @return Collection<int, object{ticket_type_id:int, name:string, issued:int, used:int, revenue:float}>
     */
    public function salesByType(int $eventId): Collection;

    /**
     * Conteo de escaneos del evento agrupados por resultado.
     *
     * @return array<string, int>
     */
    public function scanResults(int $eventId): array;
}
