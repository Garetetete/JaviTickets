<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Tour: el artista/dueño a quien pertenecen los tickets. Es la entidad raíz de
 * la jerarquía de configuración (tour → events → ticket_types).
 *
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string $artist_name
 * @property string|null $owner_name
 * @property string|null $owner_email
 * @property bool $is_active
 */
class Tour extends Model
{
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'slug', 'name', 'artist_name', 'owner_name', 'owner_email', 'is_active',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Eventos que componen el tour.
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Tipos de ticket definidos a nivel de tour.
     */
    public function ticketTypes(): HasMany
    {
        return $this->hasMany(TicketType::class);
    }

    /**
     * Scope: limita la consulta a tours activos.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Tour>  $query
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
