<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

/**
 * Usuario operador del sistema (panel y puerta). El campo `role` distingue
 * entre `admin` (acceso total) y `gate` (solo validación, opcionalmente atado a
 * un evento). Implementa JWTSubject para autenticarse con el guard `admin`.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $role  admin | gate.
 * @property int|null $event_id  Evento asignado (para operadores gate).
 * @property bool $is_active
 */
class AdminUser extends Authenticatable implements JWTSubject
{
    use Notifiable, SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'name', 'email', 'password', 'role', 'event_id', 'is_active',
    ];

    /** @var list<string> */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    /**
     * Evento asignado al operador (relevante para el rol gate).
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Indica si el usuario tiene rol de administrador.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Indica si el usuario tiene rol de operador de puerta.
     */
    public function isGate(): bool
    {
        return $this->role === 'gate';
    }

    // --- JWTSubject ---

    /**
     * Identificador que se incrusta en el claim `sub` del JWT.
     */
    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    /**
     * Claims personalizados añadidos al JWT (rol y evento asignado).
     *
     * @return array<string, mixed>
     */
    public function getJWTCustomClaims(): array
    {
        return [
            'role' => $this->role,
            'event_id' => $this->event_id,
        ];
    }
}
