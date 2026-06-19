<?php

namespace App\Exceptions;

class SeatUnavailableException extends DomainException
{
    public function __construct(string $message = 'El asiento solicitado no está disponible.')
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return 409;
    }
}
