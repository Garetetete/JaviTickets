<?php

namespace App\Console\Commands;

use App\Repositories\Contracts\TicketRepositoryInterface;
use Illuminate\Console\Command;

/**
 * Comando programado (schedule diario) que marca como `expired` los tickets
 * aún issued/active cuyos eventos ya pasaron. Uso: `php artisan tickets:expire`.
 */
class ExpireTickets extends Command
{
    /** @var string Firma del comando artisan. */
    protected $signature = 'tickets:expire';

    /** @var string Descripción mostrada en `php artisan list`. */
    protected $description = 'Marca como expirados los tickets issued/active de eventos ya pasados.';

    /**
     * Ejecuta la expiración y reporta cuántos tickets se vieron afectados.
     *
     * @return int  Código de salida del comando (SUCCESS).
     */
    public function handle(TicketRepositoryInterface $tickets): int
    {
        $count = $tickets->expirePastEvents();

        $this->info("Tickets expirados: {$count}");

        return self::SUCCESS;
    }
}
