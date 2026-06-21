<?php

namespace App\DTOs;

/**
 * DTO inmutable con los datos de un intento de validación de QR en puerta.
 * Lo construye el Controller a partir del Request y del usuario autenticado, y
 * lo consume {@see \App\Services\ValidationService::validate()}.
 */
final readonly class ValidateTicketData
{
    /**
     * @param  string  $qrToken  Token firmado leído del QR.
     * @param  int|null  $gateUserId  Id del operador (gate/admin) que escanea; se registra en el ticket y el scan_log.
     * @param  int|null  $expectedEventId  Evento esperado (para gate atado a un evento); null = sin restricción.
     * @param  string|null  $ip  IP de origen del escaneo (auditoría).
     * @param  string|null  $device  Identificador del dispositivo/puerta (p. ej. "gate-01").
     */
    public function __construct(
        public string $qrToken,
        public ?int $gateUserId = null,
        public ?int $expectedEventId = null,
        public ?string $ip = null,
        public ?string $device = null,
    ) {}
}
