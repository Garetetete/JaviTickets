<?php

namespace App\Exceptions;

/**
 * Se lanza cuando el asiento solicitado no existe, está inactivo o ya fue
 * vendido (defensa en aplicación, complementada por el índice único parcial en
 * BD). Se traduce a HTTP 409.
 */
class SeatUnavailableException extends DomainException
{
    public function __construct(string $message = 'El asiento solicitado no está disponible.')
    {
        parent::__construct($message);
    }

    /**
     * {@inheritDoc}
     */
    public function status(): int
    {
        return 409;
    }
}
