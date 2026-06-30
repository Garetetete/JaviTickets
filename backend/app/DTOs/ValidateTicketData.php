<?php

namespace App\DTOs;

/**
 * Datos para validar un QR en puerta.
 */
final class ValidateTicketData
{
    public function __construct(
        public readonly string $qrToken,
        public readonly ?int $gateUserId = null,
        public readonly ?int $expectedEventId = null,
        public readonly ?string $ip = null,
        public readonly ?string $device = null,
    ) {}
}
