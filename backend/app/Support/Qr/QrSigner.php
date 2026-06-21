<?php

namespace App\Support\Qr;

use RuntimeException;

/**
 * Firma y verificación HMAC-SHA256 del contenido del QR, con rotación de clave.
 *
 * Formato del token: "v{version}.{base64url(code)}.{base64url(hmac)}"
 * donde hmac = HMAC_SHA256(code . "|" . version, secret(version)).
 *
 * Copiar la imagen del QR no permite falsificar: cualquier alteración del
 * code o de la firma rompe la verificación.
 */
class QrSigner
{
    /**
     * @param  array<int, string>  $secrets  Mapa version => secreto HMAC (permite rotación).
     * @param  int  $currentVersion  Versión de clave activa para firmar tokens nuevos.
     */
    public function __construct(
        private readonly array $secrets,
        private readonly int $currentVersion,
    ) {}

    /**
     * Firma un code y devuelve el token que se incrusta en el QR.
     *
     * @param  string  $code  Identificador único del ticket (ULID).
     * @param  int|null  $version  Versión de clave a usar; por defecto la activa.
     * @return string  Token con formato "v{version}.{base64url(code)}.{base64url(hmac)}".
     *
     * @throws \RuntimeException  Si no hay secreto configurado para la versión.
     */
    public function sign(string $code, ?int $version = null): string
    {
        $version ??= $this->currentVersion;
        $secret = $this->secretFor($version);

        if ($secret === null || $secret === '') {
            throw new RuntimeException("No hay secreto QR configurado para la versión {$version}.");
        }

        $mac = hash_hmac('sha256', $code.'|'.$version, $secret, true);

        return sprintf('v%d.%s.%s', $version, $this->b64($code), $this->b64($mac));
    }

    /**
     * Verifica un token: comprueba formato, versión, recomputa el HMAC con el
     * secreto correspondiente y lo compara en tiempo constante.
     *
     * @param  string  $token  Token leído del QR.
     * @return QrVerifyResult  Resultado con el code y la versión si es válido; inválido en caso contrario.
     */
    public function verify(string $token): QrVerifyResult
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return QrVerifyResult::invalid();
        }

        [$versionPart, $payload, $signature] = $parts;

        if (! str_starts_with($versionPart, 'v') || ! ctype_digit(substr($versionPart, 1))) {
            return QrVerifyResult::invalid();
        }

        $version = (int) substr($versionPart, 1);
        $secret = $this->secretFor($version);

        if ($secret === null || $secret === '') {
            return QrVerifyResult::invalid();
        }

        $code = $this->unb64($payload);

        if ($code === false) {
            return QrVerifyResult::invalid();
        }

        $expected = $this->b64(hash_hmac('sha256', $code.'|'.$version, $secret, true));

        if (! hash_equals($expected, $signature)) {
            return QrVerifyResult::invalid();
        }

        return QrVerifyResult::valid($code, $version);
    }

    /**
     * Devuelve el secreto HMAC asociado a una versión de clave, o null si no existe.
     */
    private function secretFor(int $version): ?string
    {
        return $this->secrets[$version] ?? null;
    }

    /**
     * Codifica bytes en base64url (sin padding), apto para incrustar en el token.
     */
    private function b64(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    /**
     * Decodifica una cadena base64url. Devuelve false si la entrada es inválida.
     */
    private function unb64(string $value): string|false
    {
        return base64_decode(strtr($value, '-_', '+/'), true);
    }
}
