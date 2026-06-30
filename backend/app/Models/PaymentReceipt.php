<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Desprendible de pago subido en el flujo manual. Guarda la ruta del archivo
 * en disco privado y sus metadatos; el archivo solo se descarga vía endpoint
 * admin autenticado.
 *
 * @property int $id
 * @property int $order_id
 * @property string $file_path  Ruta en el disco privado (no pública).
 * @property string|null $original_name
 * @property string|null $mime_type
 * @property string|null $uploaded_by
 */
class PaymentReceipt extends Model
{
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'order_id', 'file_path', 'original_name', 'mime_type', 'uploaded_by',
    ];

    /**
     * Orden a la que pertenece el desprendible.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
