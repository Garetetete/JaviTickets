<?php

namespace App\Http\Resources;

use App\Models\ApiClient;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ApiClient
 *
 * No expone client_secret_hash ni webhook_secret.
 */
class ApiClientResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'client_id' => $this->client_id,
            'scopes' => $this->scopes,
            'is_active' => $this->is_active,
            'has_webhook_secret' => ! empty($this->webhook_secret),
            'deleted_at' => optional($this->deleted_at)?->toIso8601String(),
        ];
    }
}
