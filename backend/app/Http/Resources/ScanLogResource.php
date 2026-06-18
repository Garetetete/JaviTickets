<?php

namespace App\Http\Resources;

use App\Models\ScanLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ScanLog
 */
class ScanLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_id' => $this->ticket_id,
            'code' => $this->code,
            'event_id' => $this->event_id,
            'result' => $this->result,
            'scanned_by' => $this->scanned_by,
            'ip' => $this->ip,
            'device' => $this->device,
            'created_at' => optional($this->created_at)?->toIso8601String(),
        ];
    }
}
