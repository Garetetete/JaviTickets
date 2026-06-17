<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only + idempotencia (external_event_id único). Solo created_at.
 */
class WebhookEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'api_client_id', 'external_event_id', 'order_external_reference',
        'payload', 'signature_valid', 'processed',
    ];

    protected $casts = [
        'payload' => 'array',
        'signature_valid' => 'boolean',
        'processed' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function apiClient(): BelongsTo
    {
        return $this->belongsTo(ApiClient::class);
    }
}
