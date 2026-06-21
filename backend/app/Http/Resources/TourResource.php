<?php

namespace App\Http\Resources;

use App\Models\Tour;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serializa un modelo Tour para las respuestas de la API.
 * No usa la envoltura `data` (deshabilitada globalmente).
 *
 * @mixin Tour
 */
class TourResource extends JsonResource
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
            'slug' => $this->slug,
            'name' => $this->name,
            'artist_name' => $this->artist_name,
            'owner_name' => $this->owner_name,
            'owner_email' => $this->owner_email,
            'is_active' => $this->is_active,
            'deleted_at' => optional($this->deleted_at)?->toIso8601String(),
        ];
    }
}
