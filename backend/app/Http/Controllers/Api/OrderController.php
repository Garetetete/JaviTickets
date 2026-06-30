<?php

namespace App\Http\Controllers\Api;

use App\DTOs\OrderData;
use App\DTOs\ReceiptData;
use App\Exceptions\OrderNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UploadReceiptRequest;
use App\Http\Resources\OrderResource;
use App\Models\ApiClient;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoints de órdenes consumidos por la tienda (guard api_client + scopes).
 * Cubre el registro de la compra, la subida del desprendible (flujo manual) y
 * la reconciliación por referencia externa. Delega toda la lógica en PaymentService.
 */
class OrderController extends Controller
{
    public function __construct(
        private readonly PaymentService $payments,
        private readonly OrderRepositoryInterface $orders,
    ) {}

    /**
     * POST /orders — registra una compra. Idempotente por external_reference.
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        /** @var ApiClient $client */
        $client = $request->attributes->get('api_client');

        $order = $this->payments->createOrder(OrderData::fromArray(
            $request->validated() + ['api_client_id' => $client->id]
        ));

        return (new OrderResource($order))
            ->response()
            ->setStatusCode($order->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * POST /orders/{id}/receipt — sube desprendible (flujo manual).
     */
    public function receipt(UploadReceiptRequest $request, int $id): OrderResource
    {
        $file = $request->file('file');
        $path = $file->store('receipts'); // disco privado por defecto (local)

        $order = $this->payments->attachReceipt($id, new ReceiptData(
            filePath: $path,
            originalName: $file->getClientOriginalName(),
            mimeType: $file->getClientMimeType(),
            uploadedBy: $request->input('uploaded_by'),
        ));

        return new OrderResource($order);
    }

    /**
     * GET /orders/{externalReference} — reconciliación.
     */
    public function show(string $externalReference): OrderResource
    {
        $order = $this->orders->findByExternalReference($externalReference);

        if ($order === null) {
            throw new OrderNotFoundException;
        }

        return new OrderResource($order->load('tickets.ticketType', 'tickets.event'));
    }
}
