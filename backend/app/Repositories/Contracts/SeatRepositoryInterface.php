<?php

namespace App\Repositories\Contracts;

use App\Models\Seat;
use Illuminate\Support\Collection;

interface SeatRepositoryInterface extends RepositoryInterface
{
    /** @return Collection<int, Seat> */
    public function forEvent(int $eventId, ?string $section = null): Collection;

    public function findByLabel(int $eventId, string $section, string $label): ?Seat;

    /** IDs de asientos con un ticket vigente (issued/active/used) en el evento. */
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
     */
    public function bulkCreate(int $eventId, array $rows): int;
}
