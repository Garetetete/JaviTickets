<?php

namespace Tests\Unit\Support;

use App\Support\Qr\QrSigner;
use PHPUnit\Framework\TestCase;

class QrSignerTest extends TestCase
{
    private function signer(): QrSigner
    {
        return new QrSigner(
            secrets: [1 => 'secret-old', 2 => 'secret-new'],
            currentVersion: 2,
        );
    }

    public function test_sign_then_verify_roundtrip_with_current_version(): void
    {
        $signer = $this->signer();
        $token = $signer->sign('CODE-123');

        $result = $signer->verify($token);

        $this->assertTrue($result->valid);
        $this->assertSame('CODE-123', $result->code);
        $this->assertSame(2, $result->keyVersion);
    }

    public function test_tampered_token_is_rejected(): void
    {
        $signer = $this->signer();
        $token = $signer->sign('CODE-123');

        // Altera el último carácter de la firma.
        $tampered = substr($token, 0, -1).($token[-1] === 'A' ? 'B' : 'A');

        $this->assertFalse($signer->verify($tampered)->valid);
    }

    public function test_malformed_token_is_rejected(): void
    {
        $signer = $this->signer();

        $this->assertFalse($signer->verify('not-a-token')->valid);
        $this->assertFalse($signer->verify('v2.only-two')->valid);
    }

    public function test_old_key_version_still_verifies_after_rotation(): void
    {
        $signer = $this->signer();

        // Token firmado con la versión 1 (clave antigua).
        $oldToken = $signer->sign('CODE-OLD', version: 1);

        $result = $signer->verify($oldToken);
        $this->assertTrue($result->valid);
        $this->assertSame(1, $result->keyVersion);
    }

    public function test_unknown_version_is_rejected(): void
    {
        $signer = $this->signer();
        // Firma válida de v2 pero forzamos prefijo de versión inexistente.
        $token = $signer->sign('CODE-123');
        $forged = 'v9'.substr($token, 2);

        $this->assertFalse($signer->verify($forged)->valid);
    }
}
