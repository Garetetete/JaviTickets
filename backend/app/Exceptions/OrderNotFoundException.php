<?php

namespace App\Exceptions;

class OrderNotFoundException extends DomainException
{
    public function __construct(string $message = 'Orden no encontrada.')
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return 404;
    }
}
