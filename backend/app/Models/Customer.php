<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Comprador/titular de los tickets, con sus metadatos personales. La identidad
 * se consolida por documento (un \u00edndice \u00fanico parcial garantiza que una persona
 * = un customer con muchas \u00f3rdenes). El campo `metadata` es un caj\u00f3n JSON libre
 * para datos extra del integrador.
 *
 * @property int $id
 * @property string $first_name
 * @property string|null $second_name
 * @property string $last_name
 * @property string|null $second_last_name
 * @property string|null $document_type
 * @property string $document_number
 * @property string $email
 * @property array<string, mixed>|null $metadata  Datos arbitrarios del integrador.
 * @property-read string $full_name  Nombre completo compuesto (accessor).
 */
class Customer extends Model
{
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'first_name', 'second_name', 'last_name', 'second_last_name',
        'document_type', 'document_number', 'email', 'phone', 'address',
        'city_residence', 'country_residence', 'birth_date', 'metadata',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'birth_date' => 'date',
        'metadata' => 'array',
    ];

    /**
     * \u00d3rdenes de compra del cliente.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Tickets emitidos a nombre del cliente.
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Accessor: compone el nombre completo a partir de los campos de nombre.
     */
    public function getFullNameAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->first_name, $this->second_name,
            $this->last_name, $this->second_last_name,
        ])));
    }
}
