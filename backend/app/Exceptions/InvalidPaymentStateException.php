<?php

namespace App\Exceptions;

class InvalidPaymentStateException extends DomainException
{
    public function __construct(string $message = 'La orden está en un estado de pago que no permite esta acción.')
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return 409;
    }
}
