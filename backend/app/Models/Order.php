<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Orden de compra: la intención de pago que, una vez verificada, da lugar a la
 * emisión de uno o varios tickets. Es idempotente por `external_reference` y
 * registra el estado del pago (ver constantes STATUS_*).
 *
 * @property int $id
 * @property string|null $external_reference  Referencia externa (id WP) para idempotencia.
 * @property string $payment_status  Estado del pago (ver constantes STATUS_*).
 * @property string|null $payment_method  Método de pago declarado.
 * @property string $amount  Monto autoritativo (validado server-side).
 * @property string $currency  Moneda ISO de 3 letras.
 * @property int $quantity  Cantidad de tickets a emitir.
 * @property array<int, mixed>|null $seats  Asientos solicitados (eventos numerados).
 */
class Order extends Model
{
    use SoftDeletes;

    /** Creada; aún sin comprobante de pago. */
    public const STATUS_PENDING_PAYMENT = 'pending_payment';

    /** Con desprendible subido; a la espera de verificación manual. */
    public const STATUS_PENDING_VERIFICATION = 'pending_verification';

    /** Pago confirmado; los tickets pueden emitirse. */
    public const STATUS_VERIFIED = 'verified';

    /** Pago rechazado. */
    public const STATUS_REJECTED = 'rejected';

    /** @var list<string> */
    protected $fillable = [
        'customer_id', 'external_reference', 'api_client_id', 'payment_status',
        'payment_method', 'amount', 'currency', 'quantity', 'seats', 'ticket_type_id',
        'event_id', 'verified_by', 'verified_at', 'rejected_reason', 'notes',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'amount' => 'decimal:2',
        'quantity' => 'integer',
        'seats' => 'array',
        'verified_at' => 'datetime',
    ];

    /**
     * Comprador que realizó la orden.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Cliente máquina (tienda) que registró la orden.
     */
    public function apiClient(): BelongsTo
    {
        return $this->belongsTo(ApiClient::class);
    }

    /**
     * Tipo de ticket comprado.
     */
    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class);
    }

    /**
     * Evento asociado a la compra.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Administrador que verificó el pago (flujo manual), si aplica.
     */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'verified_by');
    }

    /**
     * Tickets emitidos para esta orden.
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Desprendibles de pago subidos para esta orden (flujo manual).
     */
    public function receipts(): HasMany
    {
        return $this->hasMany(PaymentReceipt::class);
    }

    /**
     * Indica si el pago de la orden ya está verificado.
     */
    public function isVerified(): bool
    {
        return $this->payment_status === self::STATUS_VERIFIED;
    }
}
