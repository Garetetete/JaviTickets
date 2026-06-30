<?php

namespace App\Support\Qr;

/**
 * Resultado de verificar un qr_token.
 */
final class QrVerifyResult
{
    public function __construct(
        public readonly bool $valid,
        public readonly ?string $code = null,
        public readonly ?int $keyVersion = null,
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
