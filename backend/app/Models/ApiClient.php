<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Cliente máquina que consume la API (p. ej. la tienda WordPress). Se autentica
 * con client_id/client_secret (hasheado) y actúa según sus `scopes`. Su
 * `webhook_secret` (cifrado en reposo) firma los webhooks de pago entrantes.
 *
 * @property int $id
 * @property string $name
 * @property string $client_id
 * @property array<int, string> $scopes  orders:write, tickets:read.
 * @property bool $is_active
 */
class ApiClient extends Model
{
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'name', 'client_id', 'client_secret_hash', 'webhook_secret', 'scopes', 'is_active',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'scopes' => 'array',
        'is_active' => 'boolean',
        'webhook_secret' => 'encrypted', // cifrado en reposo; se descifra al leer
    ];

    /** @var list<string> */
    protected $hidden = [
        'client_secret_hash', 'webhook_secret',
    ];

    /**
     * Órdenes registradas por este cliente.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Webhooks de pago recibidos de este cliente (idempotencia/auditoría).
     */
    public function webhookEvents(): HasMany
    {
        return $this->hasMany(WebhookEvent::class);
    }

    /**
     * Indica si el cliente posee el scope indicado.
     */
    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->scopes ?? [], true);
    }
}
