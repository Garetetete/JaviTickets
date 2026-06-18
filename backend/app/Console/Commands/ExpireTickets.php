<?php

namespace App\Console\Commands;

use App\Repositories\Contracts\TicketRepositoryInterface;
use Illuminate\Console\Command;

class ExpireTickets extends Command
{
    protected $signature = 'tickets:expire';

    protected $description = 'Marca como expirados los tickets issued/active de eventos ya pasados.';

    public function handle(TicketRepositoryInterface $tickets): int
    {
        $count = $tickets->expirePastEvents();

        $this->info("Tickets expirados: {$count}");

        return self::SUCCESS;
    }
}
