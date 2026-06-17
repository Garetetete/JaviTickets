<?php

namespace App\Repositories\Contracts;

use App\Models\PaymentReceipt;
use Illuminate\Support\Collection;

interface PaymentReceiptRepositoryInterface
{
    public function create(array $data): PaymentReceipt;

    /** @return Collection<int, PaymentReceipt> */
    public function forOrder(int $orderId): Collection;
}
