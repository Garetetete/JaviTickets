<?php

namespace App\Repositories\Contracts;

use App\Models\PaymentReceipt;
use Illuminate\Support\Collection;

interface PaymentReceiptRepositoryInterface
{
    /**
     * Crea el registro de un desprendible subido.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): PaymentReceipt;

    /**
     * Desprendibles de una orden.
     *
     * @return Collection<int, PaymentReceipt>
     */
    public function forOrder(int $orderId): Collection;

    /**
     * Desprendible por id, asegurando que pertenece a la orden indicada
     * (control de acceso). Null si no coincide.
     */
    public function findForOrder(int $receiptId, int $orderId): ?PaymentReceipt;
}
