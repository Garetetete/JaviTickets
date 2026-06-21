<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Evento concreto de un tour (ciudad/venue/fecha) con su aforo total
 * (`capacity`) y modo de asientos (`seating_type`: general o numerado).
 *
 * @property int $id
 * @property int $tour_id
 * @property string $name
 * @property string|null $city
 * @property string|null $venue
 * @property int $capacity  Aforo total del evento.
 * @property string $seating_type  general | seated.
 * @property bool $is_active
 */
class Event extends Model
{
    use SoftDeletes;

    /** Evento sin asientos numerados (entrada libre). */
    public const SEATING_GENERAL = 'general';

    /** Evento con asientos numerados. */
    public const SEATING_SEATED = 'seated';

    /** @var list<string> */
    protected $fillable = [
        'tour_id', 'slug', 'name', 'country', 'city', 'venue',
        'event_date', 'capacity', 'seating_type', 'is_active',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'event_date' => 'datetime',
        'capacity' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Indica si el evento usa asientos numerados.
     */
    public function isSeated(): bool
    {
        return $this->seating_type === self::SEATING_SEATED;
    }

    /**
     * Inventario de asientos del evento (eventos numerados).
     */
    public function seats(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Seat::class);
    }

    /**
     * Tour al que pertenece el evento.
     */
    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }

    /**
     * Tipos de ticket definidos para el evento.
     */
    public function ticketTypes(): HasMany
    {
        return $this->hasMany(TicketType::class);
    }

    /**
     * Tickets emitidos para el evento.
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Scope: limita la consulta a eventos activos.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Event>  $query
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
