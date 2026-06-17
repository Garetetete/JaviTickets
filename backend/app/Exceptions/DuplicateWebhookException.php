<?php

namespace App\Exceptions;

class DuplicateWebhookException extends DomainException
{
    public function __construct(string $message = 'Webhook ya procesado (idempotente).')
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        // Idempotente: no es un error duro para el cliente.
        return 200;
    }
}
