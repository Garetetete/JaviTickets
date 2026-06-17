<?php

namespace App\Exceptions;

class OrderNotVerifiedException extends DomainException
{
    public function __construct(string $message = 'La orden no está verificada; no se pueden emitir tickets.')
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return 409;
    }
}
