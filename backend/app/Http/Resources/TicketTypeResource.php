<?php

namespace App\Http\Resources;

use App\Models\TicketType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serializa un modelo TicketType para las respuestas de la API.
 * No usa la envoltura `data` (deshabilitada globalmente).
 *
 * @mixin TicketType
 */
class TicketTypeResource extends JsonResource
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
            'id' => $this->id,
            'tour_id' => $this->tour_id,
            'event_id' => $this->event_id,
            'slug' => $this->slug,
            'name' => $this->name,
            'price' => $this->price,
            'currency' => $this->currency,
            'quota' => $this->quota,
            'order' => $this->order,
            'is_active' => $this->is_active,
            'deleted_at' => optional($this->deleted_at)?->toIso8601String(),
        ];
    }
}
