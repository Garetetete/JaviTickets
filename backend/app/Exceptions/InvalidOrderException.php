<?php

namespace App\Exceptions;

/**
 * Se lanza cuando los datos de una orden no superan la validación de
 * integridad server-side (tipo/evento incoherentes, monto o moneda inválidos,
 * número de asientos != cantidad, …). Se traduce a HTTP 422.
 */
class InvalidOrderException extends DomainException
{
    public function __construct(string $message = 'Datos de la orden inválidos.')
    {
        parent::__construct($message);
    }

    /**
     * {@inheritDoc}
     */
    public function status(): int
    {
        return 422;
    }
}
