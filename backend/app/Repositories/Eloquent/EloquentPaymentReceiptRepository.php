<?php

namespace App\Repositories\Eloquent;

use App\Models\PaymentReceipt;
use App\Repositories\Contracts\PaymentReceiptRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Implementación Eloquent de {@see \App\Repositories\Contracts\PaymentReceiptRepositoryInterface}.
 * Es la ÚNICA capa autorizada a ejecutar consultas Eloquent sobre el modelo PaymentReceipt.
 */
class EloquentPaymentReceiptRepository implements PaymentReceiptRepositoryInterface
{
    /**
     * {@inheritDoc}
     *
     * Crea el registro del desprendible por asignación masiva.
     */
    public function create(array $data): PaymentReceipt
    {
        return PaymentReceipt::query()->create($data);
    }

    /**
     * {@inheritDoc}
     *
     * Devuelve los desprendibles de la orden, más recientes primero.
     */
    public function forOrder(int $orderId): Collection
    {
        return PaymentReceipt::query()
            ->where('order_id', $orderId)
            ->latest()
            ->get();
    }

    /**
     * {@inheritDoc}
     *
     * Busca el desprendible por id exigiendo que pertenezca a la orden indicada
     * (control de acceso). Null si no coincide.
     */
    public function findForOrder(int $receiptId, int $orderId): ?PaymentReceipt
    {
        return PaymentReceipt::query()
            ->where('id', $receiptId)
            ->where('order_id', $orderId)
            ->first();
    }
}
