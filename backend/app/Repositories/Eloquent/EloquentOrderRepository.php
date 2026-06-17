<?php

namespace App\Repositories\Eloquent;

use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentOrderRepository extends EloquentRepository implements OrderRepositoryInterface
{
    protected string $model = Order::class;

    public function findByExternalReference(string $ref): ?Order
    {
        return Order::query()->where('external_reference', $ref)->first();
    }

    public function paginateWithFilters(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return Order::query()
            ->when($filters['payment_status'] ?? null, fn ($q, $v) => $q->where('payment_status', $v))
            ->when($filters['event_id'] ?? null, fn ($q, $v) => $q->where('event_id', $v))
            ->when($filters['ticket_type_id'] ?? null, fn ($q, $v) => $q->where('ticket_type_id', $v))
            ->when($filters['customer_id'] ?? null, fn ($q, $v) => $q->where('customer_id', $v))
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->where('created_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->where('created_at', '<=', $v))
            ->latest()
            ->paginate($perPage);
    }

    public function markVerified(int $id, ?int $adminUserId): Order
    {
        $order = Order::query()->findOrFail($id);
        $order->update([
            'payment_status' => Order::STATUS_VERIFIED,
            'verified_by' => $adminUserId,
            'verified_at' => now(),
        ]);

        return $order->refresh();
    }

    public function markRejected(int $id, string $reason): Order
    {
        $order = Order::query()->findOrFail($id);
        $order->update([
            'payment_status' => Order::STATUS_REJECTED,
            'rejected_reason' => $reason,
        ]);

        return $order->refresh();
    }
}
