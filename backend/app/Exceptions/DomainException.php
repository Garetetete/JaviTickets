<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Excepción base de dominio. Los Services la lanzan ante una violación de
 * regla de negocio y el handler global (en bootstrap/app.php) la traduce a una
 * respuesta JSON con el código HTTP que indica {@see self::status()}.
 */
abstract class DomainException extends RuntimeException
{
    /**
     * Código de estado HTTP con el que se debe responder esta excepción.
     */
    abstract public function status(): int;

    /**
     * Clave de error legible por máquina (nombre corto de la clase),
     * pensada para que el cliente la interprete programáticamente.
     */
    public function errorCode(): string
    {
        return class_basename(static::class);
    }
}
