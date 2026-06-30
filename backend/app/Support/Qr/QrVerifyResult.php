<?php

namespace App\Support\Qr;

/**
 * Resultado inmutable de verificar un qr_token con {@see QrSigner::verify()}.
 * Si es válido expone el `code` del ticket y la versión de clave usada.
 */
final class QrVerifyResult
{
    /**
     * @param  bool  $valid  True si la firma del token es correcta.
     * @param  string|null  $code  Code del ticket extraído del token (solo si es válido).
     * @param  int|null  $keyVersion  Versión de clave HMAC con la que se firmó (solo si es válido).
     */
    public function __construct(
        public readonly bool $valid,
        public readonly ?string $code = null,
        public readonly ?int $keyVersion = null,
    ) {}

    /**
     * Crea un resultado inválido (firma incorrecta o token mal formado).
     */
    public static function invalid(): self
    {
        return new self(false);
    }

    /**
     * Crea un resultado válido con el code y la versión de clave verificados.
     */
    public static function valid(string $code, int $keyVersion): self
    {
        return new self(true, $code, $keyVersion);
    }
}
