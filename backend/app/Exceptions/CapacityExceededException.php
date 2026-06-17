<?php

namespace App\Exceptions;

class CapacityExceededException extends DomainException
{
    public function __construct(string $message = 'Aforo excedido para el evento o tipo de ticket.')
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return 409;
    }
}
