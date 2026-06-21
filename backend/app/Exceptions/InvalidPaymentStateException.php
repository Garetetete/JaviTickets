<?php

namespace App\Exceptions;

/**
 * Se lanza cuando se intenta una transición de pago no permitida desde el
 * estado actual de la orden (p. ej. verificar una orden ya rechazada).
 * Se traduce a HTTP 409.
 */
class InvalidPaymentStateException extends DomainException
{
    public function __construct(string $message = 'La orden está en un estado de pago que no permite esta acción.')
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
