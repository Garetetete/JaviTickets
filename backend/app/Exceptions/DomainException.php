<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Excepción base de dominio. Los Services la lanzan y el handler la traduce
 * a una respuesta JSON con el status HTTP correspondiente.
 */
abstract class DomainException extends RuntimeException
{
    abstract public function status(): int;

    /** Clave de error legible por máquina (para el cliente). */
    public function errorCode(): string
    {
        return class_basename(static::class);
    }
}
