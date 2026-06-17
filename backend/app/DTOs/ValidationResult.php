<?php

namespace App\DTOs;

/**
 * Resultado de validar un QR en puerta.
 *
 * result ∈ valid | already_used | invalid | not_paid | void | not_found | wrong_event
 */
final readonly class ValidationResult
{
    /**
     * @param  array<string, mixed>|null  $ticket  datos mínimos del asistente
     */
    public function __construct(
        public string $result,
        public ?array $ticket = null,
    ) {}

    public function isValid(): bool
    {
        return $this->result === 'valid';
    }
}
