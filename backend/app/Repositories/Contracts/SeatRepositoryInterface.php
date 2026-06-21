<?php

namespace App\Repositories\Contracts;

use App\Models\Seat;
use Illuminate\Support\Collection;

interface SeatRepositoryInterface extends RepositoryInterface
{
    /**
     * Asientos de un evento, opcionalmente filtrados por sección.
     *
     * @return Collection<int, Seat>
     */
    public function forEvent(int $eventId, ?string $section = null): Collection;

    /**
     * Busca un asiento por evento, sección y etiqueta. Null si no existe.
     */
    public function findByLabel(int $eventId, string $section, string $label): ?Seat;

    /**
     * IDs de asientos con un ticket vigente (issued/active/used) en el evento.
     *
     * @return array<int, int>
     */
    public function takenSeatIds(int $eventId): array;

    /**
     * Asientos del evento con su estado de disponibilidad.
     *
     * @return array<int, array{id:int, section:string, label:string, available:bool}>
     */
    public function availabilityForEvent(int $eventId, ?string $section = null): array;

    /**
     * Alta masiva de asientos (idempotente por unique event+section+label).
     *
     * @param  array<int, array{section?:string, label:string}>  $rows
     * @return int  Número de asientos creados.
     */
    public function bulkCreate(int $eventId, array $rows): int;
}
