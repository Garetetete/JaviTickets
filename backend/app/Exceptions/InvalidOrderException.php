<?php

namespace App\Exceptions;

class InvalidOrderException extends DomainException
{
    public function __construct(string $message = 'Datos de la orden inválidos.')
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return 422;
    }
}
