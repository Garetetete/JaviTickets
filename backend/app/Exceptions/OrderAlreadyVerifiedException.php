<?php

namespace App\Exceptions;

/**
 * Se lanza al intentar verificar una orden que ya estaba verificada.
 * Se traduce a HTTP 409.
 */
class OrderAlreadyVerifiedException extends DomainException
{
    public function __construct(string $message = 'La orden ya estaba verificada.')
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
