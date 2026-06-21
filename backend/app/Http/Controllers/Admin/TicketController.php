<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\TicketResource;
use App\Repositories\Contracts\TicketRepositoryInterface;
use App\Services\ExportService;
use App\Services\TicketIssuanceService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Panel admin: gestión de tickets (rol admin). Permite listar con filtros,
 * anular (void), reemitir (reissue) y exportar a CSV en streaming.
 */
class TicketController extends Controller
{
    public function __construct(
        private readonly TicketRepositoryInterface $tickets,
        private readonly TicketIssuanceService $issuance,
        private readonly ExportService $export,
    ) {}

    /**
     * GET /admin/tickets — lista paginada de tickets con filtros (event_id,
     * ticket_type_id, status, customer_id, fechas).
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $tickets = $this->tickets->paginateWithFilters(
            $this->filters($request),
            (int) $request->integer('per_page', 20),
        );

        $tickets->getCollection()->each(fn ($t) => $t->loadMissing('ticketType', 'event'));

        return TicketResource::collection($tickets);
    }

    /**
     * POST /admin/tickets/{id}/void — anula un ticket con un motivo obligatorio.
     * 422 si falta el motivo.
     */
    public function void(Request $request, int $id): TicketResource
    {
        $reason = $request->validate(['reason' => ['required', 'string', 'max:255']])['reason'];

        return new TicketResource($this->tickets->void($id, $reason));
    }

    /**
     * POST /admin/tickets/{id}/reissue — reemite un ticket (genera uno nuevo a
     * partir del anterior). 404 si el ticket original no existe.
     */
    public function reissue(int $id): TicketResource
    {
        $old = $this->tickets->find($id) ?? abort(404);
        $new = $this->issuance->reissue($old);

        return new TicketResource($new->loadMissing('ticketType', 'event'));
    }

    /**
     * GET /admin/tickets/export — exporta los tickets filtrados a CSV en
     * streaming.
     */
    public function export(Request $request): StreamedResponse
    {
        return $this->export->ticketsCsv($this->filters($request));
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(Request $request): array
    {
        return [
            'event_id' => $request->integer('event_id') ?: null,
            'ticket_type_id' => $request->integer('ticket_type_id') ?: null,
            'status' => $request->input('status'),
            'customer_id' => $request->integer('customer_id') ?: null,
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ];
    }
}
