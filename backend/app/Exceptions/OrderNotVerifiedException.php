<?php

namespace App\Exceptions;

/**
 * Se lanza al intentar emitir tickets de una orden cuyo pago aún no está
 * verificado. Protege la regla "solo se emiten tickets con pago verified".
 * Se traduce a HTTP 409.
 */
class OrderNotVerifiedException extends DomainException
{
    public function __construct(string $message = 'La orden no está verificada; no se pueden emitir tickets.')
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
