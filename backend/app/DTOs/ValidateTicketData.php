<?php

namespace App\DTOs;

/**
 * Datos para validar un QR en puerta.
 */
final readonly class ValidateTicketData
{
    public function __construct(
        public string $qrToken,
        public ?int $gateUserId = null,
        public ?int $expectedEventId = null,
        public ?string $ip = null,
        public ?string $device = null,
    ) {}
}
