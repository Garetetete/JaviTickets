<?php

namespace App\Exceptions;

/**
 * Se lanza cuando llega un webhook de pago cuyo external_event_id ya fue
 * procesado. Por idempotencia no es un error duro: se traduce a HTTP 200.
 */
class DuplicateWebhookException extends DomainException
{
    public function __construct(string $message = 'Webhook ya procesado (idempotente).')
    {
        parent::__construct($message);
    }

    /**
     * {@inheritDoc}
     */
    public function status(): int
    {
        // Idempotente: no es un error duro para el cliente.
        return 200;
    }
}
