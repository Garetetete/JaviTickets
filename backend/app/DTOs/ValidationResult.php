<?php

namespace App\DTOs;

/**
 * DTO inmutable con el resultado de validar un QR en puerta, devuelto por
 * {@see \App\Services\ValidationService::validate()} y traducido a JSON por el
 * Controller.
 *
 * Valores posibles de $result:
 * `valid` | `already_used` | `invalid` | `not_paid` | `void` | `not_found` | `wrong_event`.
 */
final readonly class ValidationResult
{
    /**
     * @param  string  $result  Código de resultado de negocio de la validación.
     * @param  array<string, mixed>|null  $ticket  Datos mínimos del asistente (nombre, tipo, evento, asiento); null si no aplica.
     */
    public function __construct(
        public string $result,
        public ?array $ticket = null,
    ) {}

    /**
     * Indica si la validación fue exitosa (primer ingreso correcto).
     */
    public function isValid(): bool
    {
        return $this->result === 'valid';
    }
}
