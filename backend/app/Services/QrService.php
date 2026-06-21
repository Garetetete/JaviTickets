<?php

namespace App\Services;

use App\Support\Qr\QrSigner;
use App\Support\Qr\QrVerifyResult;
use Endroid\QrCode\Builder\Builder;

/**
 * Firma, verificación y representación visual del QR.
 * No accede a BD. La lógica criptográfica vive en QrSigner.
 */
class QrService
{
    public function __construct(
        private readonly QrSigner $signer,
    ) {}

    /**
     * Firma un code y devuelve el token a incrustar en el QR.
     *
     * @param  string  $code  Identificador único del ticket (ULID).
     * @param  int|null  $version  Versión de clave a usar; por defecto la activa.
     */
    public function sign(string $code, ?int $version = null): string
    {
        return $this->signer->sign($code, $version);
    }

    /**
     * Verifica un token leído del QR (firma + versión de clave).
     */
    public function verify(string $qrToken): QrVerifyResult
    {
        return $this->signer->verify($qrToken);
    }

    /**
     * Genera la imagen del QR (PNG por defecto) y devuelve sus bytes.
     */
    public function toImage(string $qrToken): string
    {
        return Builder::create()
            ->data($qrToken)
            ->size((int) config('qr.image_size', 300))
            ->margin(10)
            ->build()
            ->getString();
    }
}
