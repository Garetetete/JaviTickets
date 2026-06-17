<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only: solo created_at (sin updated_at ni soft delete).
 */
class ScanLog extends Model
{
    public const UPDATED_AT = null;

    public const RESULT_VALID = 'valid';
    public const RESULT_ALREADY_USED = 'already_used';
    public const RESULT_INVALID_SIGNATURE = 'invalid_signature';
    public const RESULT_NOT_PAID = 'not_paid';
    public const RESULT_VOID = 'void';
    public const RESULT_NOT_FOUND = 'not_found';
    public const RESULT_WRONG_EVENT = 'wrong_event';

    protected $fillable = [
        'ticket_id', 'code', 'event_id', 'result', 'scanned_by', 'ip', 'device',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function scannedBy(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'scanned_by');
    }
}
