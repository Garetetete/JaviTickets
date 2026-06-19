<?php

namespace App\Repositories\Eloquent;

use App\Models\Seat;
use App\Models\Ticket;
use App\Repositories\Contracts\SeatRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentSeatRepository extends EloquentRepository implements SeatRepositoryInterface
{
    protected string $model = Seat::class;

    public function forEvent(int $eventId, ?string $section = null): Collection
    {
        return Seat::query()
            ->where('event_id', $eventId)
            ->when($section, fn ($q, $v) => $q->where('section', $v))
            ->orderBy('section')->orderBy('label')
            ->get();
    }

    public function findByLabel(int $eventId, string $section, string $label): ?Seat
    {
        return Seat::query()
            ->where('event_id', $eventId)
            ->where('section', $section)
            ->where('label', $label)
            ->first();
    }

    public function takenSeatIds(int $eventId): array
    {
        return Ticket::query()
            ->where('event_id', $eventId)
            ->whereNotNull('seat_id')
            ->whereIn('status', [Ticket::STATUS_ISSUED, Ticket::STATUS_ACTIVE, Ticket::STATUS_USED])
            ->pluck('seat_id')
            ->all();
    }

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
