<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use SoftDeletes;

    public const STATUS_ISSUED = 'issued';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_USED = 'used';
    public const STATUS_VOID = 'void';
    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'code', 'qr_token', 'key_version', 'order_id', 'ticket_type_id',
        'event_id', 'customer_id', 'status', 'section', 'seat', 'seat_id', 'used_at',
        'validated_by', 'voided_reason', 'metadata',
    ];

    protected $casts = [
        'key_version' => 'integer',
        'used_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'validated_by');
    }

    public function scanLogs(): HasMany
    {
        return $this->hasMany(ScanLog::class);
    }

    public function isUsable(): bool
    {
        return in_array($this->status, [self::STATUS_ISSUED, self::STATUS_ACTIVE], true);
    }
}
