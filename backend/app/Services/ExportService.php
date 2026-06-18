<?php

namespace App\Services;

use App\Repositories\Contracts\TicketRepositoryInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Exportación de registros. CSV nativo (sin dependencias). Para XLSX se puede
 * añadir maatwebsite/excel a futuro (ver SDD).
 */
class ExportService
{
    public function __construct(
        private readonly TicketRepositoryInterface $tickets,
    ) {}

    /**
     * Exporta tickets filtrados a CSV en streaming.
     *
     * @param  array<string, mixed>  $filters
     */
    public function ticketsCsv(array $filters): StreamedResponse
    {
        $columns = ['code', 'status', 'event_id', 'ticket_type_id', 'customer_id', 'used_at', 'created_at'];

        $response = new StreamedResponse(function () use ($filters, $columns) {
            $out = fopen('php://output', 'wb');
            fputcsv($out, $columns);

            // Cursor lazy: no carga todo en memoria.
            foreach ($this->tickets->cursorWithFilters($filters) as $ticket) {
                fputcsv($out, [
                    $ticket->code,
                    $ticket->status,
                    $ticket->event_id,
                    $ticket->ticket_type_id,
                    $ticket->customer_id,
                    optional($ticket->used_at)?->toIso8601String(),
                    optional($ticket->created_at)?->toIso8601String(),
                ]);
            }

            fclose($out);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="tickets.csv"');

        return $response;
    }
}
