<?php

namespace App\Http\Resources;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Event
 */
class EventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tour_id' => $this->tour_id,
            'slug' => $this->slug,
            'name' => $this->name,
            'country' => $this->country,
            'venue' => $this->venue,
            'event_date' => optional($this->event_date)?->toIso8601String(),
            'capacity' => $this->capacity,
            'is_active' => $this->is_active,
            'deleted_at' => optional($this->deleted_at)?->toIso8601String(),
        ];
    }
}
