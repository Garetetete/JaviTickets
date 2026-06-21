<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro append-only de cada webhook de pago recibido, con `external_event_id`
 * único para garantizar idempotencia (no reprocesar el mismo pago). Solo
 * `created_at`.
 *
 * @property int $id
 * @property int $api_client_id
 * @property string $external_event_id  Clave de idempotencia del evento de pago.
 * @property string|null $order_external_reference
 * @property array<string, mixed> $payload  Cuerpo recibido (auditoría).
 * @property bool $signature_valid
 * @property bool $processed
 */
class WebhookEvent extends Model
{
    /** Esta tabla no mantiene columna updated_at (append-only). */
    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $fillable = [
        'api_client_id', 'external_event_id', 'order_external_reference',
        'payload', 'signature_valid', 'processed',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'payload' => 'array',
        'signature_valid' => 'boolean',
        'processed' => 'boolean',
        'created_at' => 'datetime',
    ];

    /**
     * Cliente máquina que emitió el webhook.
     */
    public function apiClient(): BelongsTo
    {
        return $this->belongsTo(ApiClient::class);
    }
}
