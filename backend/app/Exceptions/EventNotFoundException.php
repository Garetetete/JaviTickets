<?php

namespace App\Exceptions;

class EventNotFoundException extends DomainException
{
    public function __construct(string $message = 'Evento no encontrado.')
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return 404;
    }
}
