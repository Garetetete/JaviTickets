<?php

namespace App\Repositories\Contracts;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface OrderRepositoryInterface extends RepositoryInterface
{
    public function findByExternalReference(string $ref): ?Order;

    /**
     * Filtros: payment_status, event_id, ticket_type_id, customer_id,
     * date_from, date_to.
     */
    public function paginateWithFilters(array $filters, int $perPage = 20): LengthAwarePaginator;

    public function markVerified(int $id, ?int $adminUserId): Order;

    public function markRejected(int $id, string $reason): Order;
}
