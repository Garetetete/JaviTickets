<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApiClient extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'client_id', 'client_secret_hash', 'webhook_secret', 'scopes', 'is_active',
    ];

    protected $casts = [
        'scopes' => 'array',
        'is_active' => 'boolean',
        'webhook_secret' => 'encrypted', // cifrado en reposo; se descifra al leer
    ];

    protected $hidden = [
        'client_secret_hash', 'webhook_secret',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function webhookEvents(): HasMany
    {
        return $this->hasMany(WebhookEvent::class);
    }

    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->scopes ?? [], true);
    }
}
