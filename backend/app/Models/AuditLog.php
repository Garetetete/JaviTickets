<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro append-only de cada acción de escritura del panel admin (solo
 * `created_at`). Lo escribe el middleware {@see \App\Http\Middleware\AuditAdminActions}.
 *
 * @property int $id
 * @property int|null $admin_user_id
 * @property string $method  Verbo HTTP (POST, PUT, DELETE).
 * @property string $path  Ruta afectada.
 * @property string|null $subject_id  Id del recurso afectado, si aplica.
 * @property int $status_code  Código HTTP de la respuesta.
 * @property array<string, mixed>|null $metadata
 */
class AuditLog extends Model
{
    /** Esta tabla no mantiene columna updated_at (append-only). */
    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $fillable = [
        'admin_user_id', 'method', 'path', 'subject_id', 'status_code', 'ip', 'metadata',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'status_code' => 'integer',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Administrador que realizó la acción auditada.
     */
    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class);
    }
}
