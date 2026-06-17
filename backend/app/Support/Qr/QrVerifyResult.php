<?php

namespace App\Support\Qr;

/**
 * Resultado de verificar un qr_token.
 */
final readonly class QrVerifyResult
{
    public function __construct(
        public bool $valid,
        public ?string $code = null,
        public ?int $keyVersion = null,
    ) {}

    public static function invalid(): self
    {
        return new self(false);
    }

    public static function valid(string $code, int $keyVersion): self
    {
        return new self(true, $code, $keyVersion);
    }
}
