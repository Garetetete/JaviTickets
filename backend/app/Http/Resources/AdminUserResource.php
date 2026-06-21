<?php

namespace App\Http\Resources;

use App\Models\AdminUser;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serializa un modelo AdminUser para las respuestas del panel.
 * No usa la envoltura `data` (deshabilitada globalmente).
 *
 * @mixin AdminUser
 */
class AdminUserResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'event_id' => $this->event_id,
            'is_active' => $this->is_active,
        ];
    }
}
