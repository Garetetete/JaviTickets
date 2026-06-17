<?php

namespace App\DTOs;

/**
 * Datos del desprendible de pago subido (flujo manual).
 */
final readonly class ReceiptData
{
    public function __construct(
        public string $filePath,
        public ?string $originalName = null,
        public ?string $mimeType = null,
        public ?string $uploadedBy = null,
    ) {}
}
