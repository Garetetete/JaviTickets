<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use SoftDeletes;

    public const SEATING_GENERAL = 'general';
    public const SEATING_SEATED = 'seated';

    protected $fillable = [
        'tour_id', 'slug', 'name', 'country', 'city', 'venue',
        'event_date', 'capacity', 'seating_type', 'is_active',
    ];

    protected $casts = [
        'event_date' => 'datetime',
        'capacity' => 'integer',
        'is_active' => 'boolean',
    ];

    public function isSeated(): bool
    {
        return $this->seating_type === self::SEATING_SEATED;
    }

    public function seats(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Seat::class);
    }

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }

    public function ticketTypes(): HasMany
    {
        return $this->hasMany(TicketType::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
