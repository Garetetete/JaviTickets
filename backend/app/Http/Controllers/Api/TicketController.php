<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Ticket;
use App\Repositories\Contracts\TicketRepositoryInterface;
use App\Services\QrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * Endpoints públicos de consulta de tickets para la tienda (scope tickets:read):
 * verificación de validez (sin marcar usado) y obtención de la imagen del QR.
 */
class TicketController extends Controller
{
    public function __construct(
        private readonly TicketRepositoryInterface $tickets,
        private readonly QrService $qr,
    ) {}

    /**
     * GET /tickets/{code}/verify — valida estado SIN marcar usado.
     */
    public function verify(string $code): JsonResponse
    {
        $ticket = $this->tickets->findByCode($code);

        if ($ticket === null) {
            return response()->json(['message' => 'Ticket no encontrado.', 'valid' => false], 404);
        }

        $ticket->loadMissing('order', 'ticketType', 'event');
        $paid = optional($ticket->order)->payment_status === Order::STATUS_VERIFIED;
        $valid = $paid && in_array($ticket->status, [Ticket::STATUS_ISSUED, Ticket::STATUS_ACTIVE], true);

        return response()->json([
            'code' => $ticket->code,
            'status' => $ticket->status,
            'valid' => $valid,
            'event' => optional($ticket->event)->name,
            'ticket_type' => optional($ticket->ticketType)->name,
        ]);
    }

    /**
     * GET /tickets/{code}/image — imagen PNG del QR.
     */
    public function image(string $code): Response
    {
        $ticket = $this->tickets->findByCode($code);

        if ($ticket === null) {
            return response('Ticket no encontrado.', 404);
        }

        return response($this->qr->toImage($ticket->qr_token), 200, [
            'Content-Type' => 'image/png',
        ]);
    }
}
