<?php

namespace App\Http\Resources;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Ticket
 */
class TicketResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'code' => $this->code,
            'qr_token' => $this->qr_token,
            'qr_image_url' => url("/api/v1/tickets/{$this->code}/image"),
            'status' => $this->status,
            'ticket_type' => $this->whenLoaded('ticketType', fn () => $this->ticketType->name),
            'event' => $this->whenLoaded('event', fn () => $this->event->name),
            'used_at' => optional($this->used_at)?->toIso8601String(),
        ];
    }
}
