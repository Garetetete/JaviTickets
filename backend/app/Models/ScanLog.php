<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bitácora append-only de cada intento de validación en puerta (solo
 * `created_at`, sin updated_at ni soft delete). Registra el resultado de cada
 * escaneo, el operador y el dispositivo, para auditoría y anti-doble-entrada.
 *
 * @property int $id
 * @property int|null $ticket_id
 * @property string|null $code
 * @property int|null $event_id
 * @property string $result  Resultado del escaneo (ver constantes RESULT_*).
 * @property int|null $scanned_by  Operador (admin_users) que escaneó.
 * @property \Illuminate\Support\Carbon $created_at
 */
class ScanLog extends Model
{
    /** Esta tabla no mantiene columna updated_at (append-only). */
    public const UPDATED_AT = null;

    /** Escaneo válido: primer ingreso correcto. */
    public const RESULT_VALID = 'valid';

    /** El ticket ya había sido usado. */
    public const RESULT_ALREADY_USED = 'already_used';

    /** Firma del QR inválida o token manipulado. */
    public const RESULT_INVALID_SIGNATURE = 'invalid_signature';

    /** El pago de la orden no está verificado. */
    public const RESULT_NOT_PAID = 'not_paid';

    /** El ticket estaba anulado. */
    public const RESULT_VOID = 'void';

    /** No existe ticket con ese code. */
    public const RESULT_NOT_FOUND = 'not_found';

    /** El ticket no corresponde al evento esperado de la puerta. */
    public const RESULT_WRONG_EVENT = 'wrong_event';

    /** @var list<string> */
    protected $fillable = [
        'ticket_id', 'code', 'event_id', 'result', 'scanned_by', 'ip', 'device',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Ticket escaneado (si se encontró).
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Evento en cuyo contexto se hizo el escaneo.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Operador (admin/gate) que realizó el escaneo.
     */
    public function scannedBy(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'scanned_by');
    }
}
