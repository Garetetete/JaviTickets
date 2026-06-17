<?php

namespace App\Repositories\Eloquent;

use App\Models\PaymentReceipt;
use App\Repositories\Contracts\PaymentReceiptRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentPaymentReceiptRepository implements PaymentReceiptRepositoryInterface
{
    public function create(array $data): PaymentReceipt
    {
        return PaymentReceipt::query()->create($data);
    }

    public function forOrder(int $orderId): Collection
    {
        return PaymentReceipt::query()
            ->where('order_id', $orderId)
            ->latest()
            ->get();
    }
}
