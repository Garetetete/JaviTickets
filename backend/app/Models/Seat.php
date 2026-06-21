<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Asiento del inventario de un evento numerado. La unicidad de venta (un
 * asiento = un ticket vigente) se garantiza con un índice único parcial en BD.
 *
 * @property int $id
 * @property int $event_id
 * @property string $section  Sección (p. ej. "VIP", "GENERAL").
 * @property string $label  Etiqueta del asiento (p. ej. "A-12").
 * @property bool $is_active
 */
class Seat extends Model
{
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'event_id', 'section', 'label', 'is_active',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Evento al que pertenece el asiento.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Tickets asociados al asiento (a lo sumo uno vigente).
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}
