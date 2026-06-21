<?php

namespace App\Repositories\Eloquent;

use App\Models\Ticket;
use App\Repositories\Contracts\TicketRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;

/**
 * Implementación Eloquent de {@see \App\Repositories\Contracts\TicketRepositoryInterface}.
 * Es la ÚNICA capa autorizada a ejecutar consultas Eloquent sobre el modelo Ticket.
 */
class EloquentTicketRepository extends EloquentRepository implements TicketRepositoryInterface
{
    /** Modelo Eloquent gestionado por este repositorio. */
    protected string $model = Ticket::class;

    /**
     * {@inheritDoc}
     *
     * Consulta el primer Ticket cuyo code único coincida.
     */
    public function findByCode(string $code): ?Ticket
    {
        return Ticket::query()->where('code', $code)->first();
    }

    /**
     * {@inheritDoc}
     *
     * Bloquea la fila del ticket con lockForUpdate (anti-doble-entrada); debe
     * ejecutarse dentro de una transacción.
     */
    public function lockByCodeForUpdate(string $code): ?Ticket
    {
        return Ticket::query()
            ->where('code', $code)
            ->lockForUpdate()
            ->first();
    }

    /**
     * {@inheritDoc}
     *
     * Crea los tickets uno a uno y los devuelve en una colección.
     */
    public function createMany(array $rows): Collection
    {
        $created = collect();

        foreach ($rows as $row) {
            $created->push(Ticket::query()->create($row));
        }

        return $created;
    }

    /**
     * {@inheritDoc}
     *
     * Actualiza el ticket a used registrando used_at y validated_by, y devuelve
     * el modelo recargado.
     */
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

    /**
     * {@inheritDoc}
     *
     * Actualiza el ticket a void registrando el motivo y devuelve el modelo recargado.
     */
    public function void(int $id, string $reason): Ticket
    {
        $ticket = Ticket::query()->findOrFail($id);
        $ticket->update([
            'status' => Ticket::STATUS_VOID,
            'voided_reason' => $reason,
        ]);

        return $ticket->refresh();
    }

    /**
     * {@inheritDoc}
     *
     * Aplica los filtros comunes (filtered) y pagina por fecha descendente.
     */
    public function paginateWithFilters(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->filtered($filters)->latest()->paginate($perPage);
    }

    /**
     * {@inheritDoc}
     *
     * Aplica los filtros comunes (filtered) y devuelve un cursor lazy ordenado
     * por id para exportaciones de bajo consumo de memoria.
     */
    public function cursorWithFilters(array $filters): LazyCollection
    {
        return $this->filtered($filters)->orderBy('id')->lazy();
    }

    /**
     * {@inheritDoc}
     *
     * Localiza los eventos ya pasados y actualiza en lote a expired sus tickets
     * issued/active.
     */
    public function expirePastEvents(): int
    {
        $pastEventIds = \App\Models\Event::query()
            ->whereNotNull('event_date')
            ->where('event_date', '<', now())
            ->pluck('id');

        if ($pastEventIds->isEmpty()) {
            return 0;
        }

        return Ticket::query()
            ->whereIn('event_id', $pastEventIds)
            ->whereIn('status', [Ticket::STATUS_ISSUED, Ticket::STATUS_ACTIVE])
            ->update(['status' => Ticket::STATUS_EXPIRED]);
    }

    /**
     * Construye el query base de Ticket aplicando los filtros opcionales
     * compartidos por el listado y el cursor de exportación.
     *
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
