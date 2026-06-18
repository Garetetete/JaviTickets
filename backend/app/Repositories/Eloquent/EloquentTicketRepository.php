<?php

namespace App\Repositories\Eloquent;

use App\Models\Ticket;
use App\Repositories\Contracts\TicketRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;

class EloquentTicketRepository extends EloquentRepository implements TicketRepositoryInterface
{
    protected string $model = Ticket::class;

    public function findByCode(string $code): ?Ticket
    {
        return Ticket::query()->where('code', $code)->first();
    }

    public function lockByCodeForUpdate(string $code): ?Ticket
    {
        return Ticket::query()
            ->where('code', $code)
            ->lockForUpdate()
            ->first();
    }

    public function createMany(array $rows): Collection
    {
        $created = collect();

        foreach ($rows as $row) {
            $created->push(Ticket::query()->create($row));
        }

        return $created;
    }

    public function markUsed(int $id, ?int $gateUserId): Ticket
    {
        $ticket = Ticket::query()->findOrFail($id);
        $ticket->update([
            'status' => Ticket::STATUS_USED,
            'used_at' => now(),
            'validated_by' => $gateUserId,
        ]);

        return $ticket->refresh();
    }

    public function void(int $id, string $reason): Ticket
    {
        $ticket = Ticket::query()->findOrFail($id);
        $ticket->update([
            'status' => Ticket::STATUS_VOID,
            'voided_reason' => $reason,
        ]);

        return $ticket->refresh();
    }

    public function paginateWithFilters(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->filtered($filters)->latest()->paginate($perPage);
    }

    public function cursorWithFilters(array $filters): LazyCollection
    {
        return $this->filtered($filters)->orderBy('id')->lazy();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function filtered(array $filters): Builder
    {
        return Ticket::query()
            ->when($filters['event_id'] ?? null, fn ($q, $v) => $q->where('event_id', $v))
            ->when($filters['ticket_type_id'] ?? null, fn ($q, $v) => $q->where('ticket_type_id', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['customer_id'] ?? null, fn ($q, $v) => $q->where('customer_id', $v))
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->where('created_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->where('created_at', '<=', $v));
    }
}
