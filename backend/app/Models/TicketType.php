<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Tipo de ticket (Normal/Premium/Diamante…) con su precio, moneda y cupo
 * opcional (`quota`). Se define por tour y, opcionalmente, por evento.
 *
 * @property int $id
 * @property int $tour_id
 * @property int|null $event_id
 * @property string $slug
 * @property string $name
 * @property string $price
 * @property string|null $currency
 * @property int|null $quota  Cupo máximo de este tipo (null = sin límite propio).
 * @property int|null $order  Orden de presentación.
 * @property bool $is_active
 */
class TicketType extends Model
{
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'tour_id', 'event_id', 'slug', 'name',
        'price', 'currency', 'quota', 'order', 'is_active',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'price' => 'decimal:2',
        'quota' => 'integer',
        'order' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Tour al que pertenece el tipo de ticket.
     */
    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }

    /**
     * Evento específico al que aplica (si el tipo es por evento).
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Tickets emitidos de este tipo.
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Scope: limita la consulta a tipos activos.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<TicketType>  $query
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
