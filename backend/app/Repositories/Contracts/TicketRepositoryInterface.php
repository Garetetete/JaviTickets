<?php

namespace App\Repositories\Contracts;

use App\Models\Ticket;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;

interface TicketRepositoryInterface extends RepositoryInterface
{
    /**
     * Busca un ticket por su code único. Null si no existe.
     */
    public function findByCode(string $code): ?Ticket;

    /**
     * SELECT ... FOR UPDATE por code — debe invocarse dentro de transacción.
     * Bloquea la fila para el flujo anti-doble-entrada.
     */
    public function lockByCodeForUpdate(string $code): ?Ticket;

    /**
     * Emisión en lote de tickets.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return Collection<int, Ticket>
     */
    public function createMany(array $rows): Collection;

    /**
     * Marca el ticket como usado (status used, used_at, validated_by).
     *
     * @param  int|null  $gateUserId  Operador que valida.
     */
    public function markUsed(int $id, ?int $gateUserId): Ticket;

    /**
     * Anula el ticket (status void) registrando el motivo.
     */
    public function void(int $id, string $reason): Ticket;

    /**
     * Listado paginado con filtros: event_id, ticket_type_id, status,
     * customer_id, date_from, date_to.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginateWithFilters(array $filters, int $perPage = 20): LengthAwarePaginator;

    /**
     * Iterador lazy (cursor) con los mismos filtros, para exportaciones.
     *
     * @param  array<string, mixed>  $filters
     * @return LazyCollection<int, Ticket>
     */
    public function cursorWithFilters(array $filters): LazyCollection;

    /**
     * Marca como `expired` los tickets issued/active de eventos ya pasados.
     *
     * @return int  Número de tickets afectados.
     */
    public function expirePastEvents(): int;
}
