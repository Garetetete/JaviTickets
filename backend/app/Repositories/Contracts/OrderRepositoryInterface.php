<?php

namespace App\Repositories\Contracts;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface OrderRepositoryInterface extends RepositoryInterface
{
    /**
     * Busca una orden por su referencia externa (idempotencia/reconciliación).
     */
    public function findByExternalReference(string $ref): ?Order;

    /**
     * Listado paginado con filtros: payment_status, event_id, ticket_type_id,
     * customer_id, date_from, date_to.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginateWithFilters(array $filters, int $perPage = 20): LengthAwarePaginator;

    /**
     * Marca la orden como verificada (pago confirmado).
     *
     * @param  int|null  $adminUserId  Admin que verificó (flujo manual).
     */
    public function markVerified(int $id, ?int $adminUserId): Order;

    /**
     * Marca la orden como rechazada registrando el motivo.
     */
    public function markRejected(int $id, string $reason): Order;
}
