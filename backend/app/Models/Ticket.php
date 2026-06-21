<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Ticket: el QR en sí. Es la entidad central del dominio. Guarda el `code`
 * único, el `qr_token` firmado, la versión de clave HMAC y su estado en el
 * ciclo de vida (issued → active → used | void | expired). Pertenece a una
 * orden, un tipo, un evento y un comprador, y opcionalmente a un asiento.
 *
 * @property int $id
 * @property string $code  Identificador único (ULID), base del qr_token.
 * @property string $qr_token  Token firmado embebido en el QR.
 * @property int $key_version  Versión de clave HMAC con la que se firmó.
 * @property string $status  Estado actual (ver constantes STATUS_*).
 * @property string|null $section  Sección del asiento (eventos numerados).
 * @property string|null $seat  Etiqueta del asiento.
 * @property \Illuminate\Support\Carbon|null $used_at  Momento de validación en puerta.
 * @property int|null $validated_by  Operador (admin_users) que validó el ticket.
 * @property array<string, mixed>|null $metadata  Snapshot del titular al emitir.
 */
class Ticket extends Model
{
    use SoftDeletes;

    /** Emitido pero aún no activado. */
    public const STATUS_ISSUED = 'issued';

    /** Activo y válido para ingresar. */
    public const STATUS_ACTIVE = 'active';

    /** Ya validado en puerta (consumido). */
    public const STATUS_USED = 'used';

    /** Anulado manualmente. */
    public const STATUS_VOID = 'void';

    /** Expirado por vencimiento. */
    public const STATUS_EXPIRED = 'expired';

    /** @var list<string> */
    protected $fillable = [
        'code', 'qr_token', 'key_version', 'order_id', 'ticket_type_id',
        'event_id', 'customer_id', 'status', 'section', 'seat', 'seat_id', 'used_at',
        'validated_by', 'voided_reason', 'metadata',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'key_version' => 'integer',
        'used_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Orden de compra a la que pertenece el ticket.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Tipo de ticket (Normal/Premium/Diamante…) comprado.
     */
    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class);
    }

    /**
     * Evento al que da acceso el ticket.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Comprador/titular del ticket.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Operador (admin/gate) que validó el ticket en puerta, si ya fue usado.
     */
    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'validated_by');
    }

    /**
     * Bitácora (append-only) de todos los intentos de escaneo del ticket.
     */
    public function scanLogs(): HasMany
    {
        return $this->hasMany(ScanLog::class);
    }

    /**
     * Indica si el ticket aún puede usarse (estado issued o active).
     */
    public function isUsable(): bool
    {
        return in_array($this->status, [self::STATUS_ISSUED, self::STATUS_ACTIVE], true);
    }
}
