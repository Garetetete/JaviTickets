<?php

namespace App\Http\Controllers\Api;

use App\DTOs\WebhookPaymentData;
use App\Http\Controllers\Controller;
use App\Http\Requests\WebhookPaymentRequest;
use App\Http\Resources\TicketResource;
use App\Models\ApiClient;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;

class WebhookController extends Controller
{
    public function __construct(
        private readonly PaymentService $payments,
    ) {}

    /**
     * POST /webhooks/payment — pago confirmado (firma ya verificada por
     * el middleware verify.webhook). Idempotente por external_event_id.
     */
    public function payment(WebhookPaymentRequest $request): JsonResponse
    {
        /** @var ApiClient $client */
        $client = $request->attributes->get('api_client');

        $tickets = $this->payments->handleWebhook(new WebhookPaymentData(
            externalEventId: $request->input('external_event_id'),
            externalReference: $request->input('external_reference'),
            status: $request->input('status'),
            apiClientId: $client->id,
            payload: $request->all(),
            signatureValid: true,
            amount: $request->input('amount') !== null ? (float) $request->input('amount') : null,
        ));

        $tickets->each(fn ($ticket) => $ticket->loadMissing('ticketType', 'event'));

        return response()->json([
            'external_reference' => $request->input('external_reference'),
            'payment_status' => 'verified',
            'tickets' => TicketResource::collection($tickets),
        ]);
    }
}
