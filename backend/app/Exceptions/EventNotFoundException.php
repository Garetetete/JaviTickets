<?php

namespace App\Exceptions;

/**
 * Se lanza cuando se referencia un evento inexistente. Se traduce a HTTP 404.
 */
class EventNotFoundException extends DomainException
{
    public function __construct(string $message = 'Evento no encontrado.')
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
