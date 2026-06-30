<?php

namespace App\DTOs;

/**
 * Datos del desprendible de pago subido (flujo manual).
 */
final class ReceiptData
{
    public function __construct(
        public readonly string $filePath,
        public readonly ?string $originalName = null,
        public readonly ?string $mimeType = null,
        public readonly ?string $uploadedBy = null,
    ) {}
}
