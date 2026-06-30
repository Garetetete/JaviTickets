<?php

namespace App\Exceptions;

/**
 * Se lanza cuando se referencia una orden inexistente. Se traduce a HTTP 404.
 */
class OrderNotFoundException extends DomainException
{
    public function __construct(string $message = 'Orden no encontrada.')
    {
        parent::__construct($message);
    }

    /**
     * {@inheritDoc}
     */
    public function status(): int
    {
        return 404;
    }
}
