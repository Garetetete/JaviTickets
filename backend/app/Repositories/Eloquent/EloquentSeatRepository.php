<?php

namespace App\Repositories\Eloquent;

use App\Models\Seat;
use App\Models\Ticket;
use App\Repositories\Contracts\SeatRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Implementación Eloquent de {@see \App\Repositories\Contracts\SeatRepositoryInterface}.
 * Es la ÚNICA capa autorizada a ejecutar consultas Eloquent sobre el modelo Seat.
 */
class EloquentSeatRepository extends EloquentRepository implements SeatRepositoryInterface
{
    /** Modelo Eloquent gestionado por este repositorio. */
    protected string $model = Seat::class;

    /**
     * {@inheritDoc}
     *
     * Filtra por evento y opcionalmente por sección, ordenando por sección y etiqueta.
     */
    public function forEvent(int $eventId, ?string $section = null): Collection
    {
        return Seat::query()
            ->where('event_id', $eventId)
            ->when($section, fn ($q, $v) => $q->where('section', $v))
            ->orderBy('section')->orderBy('label')
            ->get();
    }

    /**
     * {@inheritDoc}
     *
     * Consulta el primer Seat que coincida en evento, sección y etiqueta.
     */
    public function findByLabel(int $eventId, string $section, string $label): ?Seat
    {
        return Seat::query()
            ->where('event_id', $eventId)
            ->where('section', $section)
            ->where('label', $label)
            ->first();
    }

    /**
     * {@inheritDoc}
     *
     * Devuelve los seat_id con un ticket vigente (issued/active/used) en el evento.
     */
    public function takenSeatIds(int $eventId): array
    {
        return Ticket::query()
            ->where('event_id', $eventId)
            ->whereNotNull('seat_id')
            ->whereIn('status', [Ticket::STATUS_ISSUED, Ticket::STATUS_ACTIVE, Ticket::STATUS_USED])
            ->pluck('seat_id')
            ->all();
    }

    /**
     * {@inheritDoc}
     *
     * Combina los asientos del evento con el conjunto de ocupados para marcar
     * su disponibilidad (activo y no ocupado).
     */
    public function availabilityForEvent(int $eventId, ?string $section = null): array
    {
        $taken = array_flip($this->takenSeatIds($eventId));

        return $this->forEvent($eventId, $section)
            ->map(fn (Seat $s) => [
                'id' => $s->id,
                'section' => $s->section,
                'label' => $s->label,
                'available' => $s->is_active && ! isset($taken[$s->id]),
            ])
            ->all();
    }

    /**
     * {@inheritDoc}
     *
     * Inserta cada asiento con firstOrCreate (idempotente por unique
     * event+section+label) y devuelve el número procesado.
     */
    public function bulkCreate(int $eventId, array $rows): int
    {
        $count = 0;

        foreach ($rows as $row) {
            Seat::query()->firstOrCreate(
                [
                    'event_id' => $eventId,
                    'section' => $row['section'] ?? 'GENERAL',
                    'label' => $row['label'],
                ],
                ['is_active' => true]
            );
            $count++;
        }

        return $count;
    }
}
