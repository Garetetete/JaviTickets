<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TicketTypeRequest;
use App\Http\Resources\TicketTypeResource;
use App\Repositories\Contracts\TicketTypeRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Panel admin: CRUD de tipos de ticket (rol admin). Soporta soft-delete/restore
 * y el parámetro ?with_trashed=1. Delega la persistencia en el repositorio.
 */
class TicketTypeController extends Controller
{
    public function __construct(
        private readonly TicketTypeRepositoryInterface $ticketTypes,
    ) {}

    /**
     * GET /admin/ticket-types — lista paginada de tipos de ticket (?tour_id,
     * ?event_id, ?with_trashed=1, ?per_page).
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        return TicketTypeResource::collection($this->ticketTypes->paginate(
            filters: [
                'tour_id' => $request->integer('tour_id') ?: null,
                'event_id' => $request->integer('event_id') ?: null,
            ],
            perPage: (int) $request->integer('per_page', 20),
            withTrashed: $request->boolean('with_trashed'),
        ));
    }

    /**
     * POST /admin/ticket-types — crea un tipo de ticket. Responde 201. 422 si la
     * validación falla.
     */
    public function store(TicketTypeRequest $request): JsonResponse
    {
        $type = $this->ticketTypes->create($request->validated());

        return (new TicketTypeResource($type))->response()->setStatusCode(201);
    }

    /**
     * GET /admin/ticket-types/{id} — muestra un tipo de ticket. 404 si no existe.
     */
    public function show(int $id): TicketTypeResource
    {
        return new TicketTypeResource($this->ticketTypes->find($id) ?? abort(404));
    }

    /**
     * PUT /admin/ticket-types/{id} — actualiza un tipo de ticket. 422 si la
     * validación falla.
     */
    public function update(TicketTypeRequest $request, int $id): TicketTypeResource
    {
        return new TicketTypeResource($this->ticketTypes->update($id, $request->validated()));
    }

    /**
     * DELETE /admin/ticket-types/{id} — soft-delete del tipo de ticket.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->ticketTypes->delete($id);

        return response()->json(['message' => 'Tipo de ticket eliminado.']);
    }

    /**
     * POST /admin/ticket-types/{id}/restore — restaura un tipo de ticket borrado.
     */
    public function restore(int $id): JsonResponse
    {
        $this->ticketTypes->restore($id);

        return response()->json(['message' => 'Tipo de ticket restaurado.']);
    }
}
