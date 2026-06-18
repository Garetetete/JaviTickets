<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\OrderNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectOrderRequest;
use App\Http\Resources\OrderResource;
use App\Http\Resources\TicketResource;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\PaymentReceiptRepositoryInterface;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly PaymentService $payments,
        private readonly PaymentReceiptRepositoryInterface $receipts,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $orders = $this->orders->paginateWithFilters([
            'payment_status' => $request->input('payment_status'),
            'event_id' => $request->integer('event_id') ?: null,
            'ticket_type_id' => $request->integer('ticket_type_id') ?: null,
            'customer_id' => $request->integer('customer_id') ?: null,
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ], (int) $request->integer('per_page', 20));

        return OrderResource::collection($orders);
    }

    public function show(int $id): OrderResource
    {
        $order = $this->orders->find($id);

        if ($order === null) {
            throw new OrderNotFoundException;
        }

        return new OrderResource($order->load('customer', 'receipts', 'tickets'));
    }

    /** Verificación manual de pago: emite tickets. */
    public function verify(int $id): JsonResponse
    {
        $adminId = Auth::guard('admin')->id();
        $tickets = $this->payments->verifyManually($id, (int) $adminId);

        $tickets->each(fn ($t) => $t->loadMissing('ticketType', 'event'));

        return response()->json([
            'order_id' => $id,
            'payment_status' => 'verified',
            'tickets' => TicketResource::collection($tickets),
        ]);
    }

    public function reject(RejectOrderRequest $request, int $id): OrderResource
    {
        return new OrderResource($this->payments->rejectManually($id, $request->validated('reason')));
    }

    /** Lista los desprendibles subidos para una orden. */
    public function receipts(int $id): JsonResponse
    {
        return response()->json([
            'order_id' => $id,
            'receipts' => $this->receipts->forOrder($id)->map(fn ($r) => [
                'id' => $r->id,
                'original_name' => $r->original_name,
                'mime_type' => $r->mime_type,
                'uploaded_by' => $r->uploaded_by,
                'uploaded_at' => optional($r->created_at)?->toIso8601String(),
                'download_url' => url("/api/v1/admin/orders/{$id}/receipts/{$r->id}/download"),
            ]),
        ]);
    }

    /** Descarga segura del desprendible (verifica que pertenece a la orden). */
    public function downloadReceipt(int $orderId, int $receiptId): StreamedResponse
    {
        $receipt = $this->receipts->findForOrder($receiptId, $orderId) ?? abort(404);

        abort_unless(Storage::exists($receipt->file_path), 404, 'Archivo no encontrado.');

        return Storage::download($receipt->file_path, $receipt->original_name ?? "receipt-{$receipt->id}");
    }
}

