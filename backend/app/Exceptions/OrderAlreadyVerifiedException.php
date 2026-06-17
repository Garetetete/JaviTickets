<?php

namespace App\Exceptions;

class OrderAlreadyVerifiedException extends DomainException
{
    public function __construct(string $message = 'La orden ya estaba verificada.')
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return 409;
    }
}
