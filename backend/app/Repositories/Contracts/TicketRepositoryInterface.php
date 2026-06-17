<?php

namespace App\Repositories\Contracts;

use App\Models\Ticket;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

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
}
