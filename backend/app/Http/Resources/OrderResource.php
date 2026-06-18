<?php

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Order
 */
class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'order_id' => $this->id,
            'external_reference' => $this->external_reference,
            'payment_status' => $this->payment_status,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'quantity' => $this->quantity,
            'event_id' => $this->event_id,
            'ticket_type_id' => $this->ticket_type_id,
            'tickets' => TicketResource::collection($this->whenLoaded('tickets')),
        ];
    }
}
