<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'first_name', 'second_name', 'last_name', 'second_last_name',
        'document_type', 'document_number', 'email', 'phone', 'address',
        'city_residence', 'country_residence', 'birth_date', 'metadata',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'metadata' => 'array',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->first_name, $this->second_name,
            $this->last_name, $this->second_last_name,
        ])));
    }
}
