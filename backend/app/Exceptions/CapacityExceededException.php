<?php

namespace App\Exceptions;

/**
 * Se lanza cuando emitir los tickets de una orden superaría el aforo del evento
 * o el cupo del tipo de ticket. Se traduce a HTTP 409 (conflicto).
 */
class CapacityExceededException extends DomainException
{
    public function __construct(string $message = 'Aforo excedido para el evento o tipo de ticket.')
    {
        parent::__construct($message);
    }

    /**
     * {@inheritDoc}
     */
    public function status(): int
    {
        return 409;
    }
}
