<?php

namespace App\DTOs;

/**
 * DTO inmutable con los metadatos del desprendible de pago subido en el flujo
 * manual. El archivo físico ya fue guardado en disco privado por el Controller;
 * este objeto transporta la ruta y datos descriptivos hacia el Service para
 * crear la fila en `payment_receipts`.
 */
final class ReceiptData
{
    /**
     * @param  string  $filePath  Ruta relativa del archivo en el disco privado (p. ej. "receipts/abc.pdf").
     * @param  string|null  $originalName  Nombre original del archivo subido por el comprador.
     * @param  string|null  $mimeType  Tipo MIME detectado (image/png, application/pdf, …).
     * @param  string|null  $uploadedBy  Identificador de quién subió el desprendible (email/cliente).
     */
    public function __construct(
        public readonly string $filePath,
        public readonly ?string $originalName = null,
        public readonly ?string $mimeType = null,
        public readonly ?string $uploadedBy = null,
    ) {}
}
