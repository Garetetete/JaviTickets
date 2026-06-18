<?php

namespace App\Repositories\Contracts;

use App\Models\Ticket;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;

interface TicketRepositoryInterface extends RepositoryInterface
{
    public function findByCode(string $code): ?Ticket;

    /** SELECT ... FOR UPDATE por code — debe invocarse dentro de transacción. */
    public function lockByCodeForUpdate(string $code): ?Ticket;

    /**
     * Emisión en lote.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return Collection<int, Ticket>
     */
    public function createMany(array $rows): Collection;

    public function markUsed(int $id, ?int $gateUserId): Ticket;

    public function void(int $id, string $reason): Ticket;

    /** Filtros: event_id, ticket_type_id, status, customer_id, date_from, date_to. */
    public function paginateWithFilters(array $filters, int $perPage = 20): LengthAwarePaginator;

    /** Iterador lazy (cursor) con los mismos filtros, para exportaciones. */
    public function cursorWithFilters(array $filters): LazyCollection;

    /**
     * Marca como `expired` los tickets issued/active de eventos ya pasados.
     * Devuelve el número de tickets afectados.
     */
    public function expirePastEvents(): int;
}
