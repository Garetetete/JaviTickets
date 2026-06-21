<?php

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serializa un modelo Order para las respuestas de la API.
 * No usa la envoltura `data` (deshabilitada globalmente).
 *
 * @mixin Order
 */
class OrderResource extends JsonResource
{
    /**
     * Transforma el recurso en el array de salida JSON.
     *
     * @param  \Illuminate\Http\Request  $request
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
            'payment_method' => $this->payment_method,
            'verified_at' => optional($this->verified_at)?->toIso8601String(),
            'rejected_reason' => $this->rejected_reason,
            'customer' => $this->whenLoaded('customer'),
            'receipts' => $this->whenLoaded('receipts'),
            'tickets' => TicketResource::collection($this->whenLoaded('tickets')),
        ];
    }
}
