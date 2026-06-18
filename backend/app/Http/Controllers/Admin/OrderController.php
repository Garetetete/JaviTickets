<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\OrderNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectOrderRequest;
use App\Http\Resources\OrderResource;
use App\Http\Resources\TicketResource;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly PaymentService $payments,
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
}
