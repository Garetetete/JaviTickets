<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EventRequest;
use App\Http\Resources\EventResource;
use App\Repositories\Contracts\EventRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Panel admin: CRUD de eventos (rol admin). Soporta soft-delete/restore y el
 * parámetro ?with_trashed=1. Delega la persistencia en el repositorio.
 */
class EventController extends Controller
{
    public function __construct(
        private readonly EventRepositoryInterface $events,
    ) {}

    /**
     * GET /admin/events — lista paginada de eventos (?tour_id, ?with_trashed=1, ?per_page).
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        return EventResource::collection($this->events->paginate(
            filters: ['tour_id' => $request->integer('tour_id') ?: null],
            perPage: (int) $request->integer('per_page', 20),
            withTrashed: $request->boolean('with_trashed'),
        ));
    }

    /**
     * POST /admin/events — crea un evento. Responde 201. 422 si la validación falla.
     */
    public function store(EventRequest $request): JsonResponse
    {
        $event = $this->events->create($request->validated());

        return (new EventResource($event))->response()->setStatusCode(201);
    }

    /**
     * GET /admin/events/{id} — muestra un evento. 404 si no existe.
     */
    public function show(int $id): EventResource
    {
        return new EventResource($this->events->find($id) ?? abort(404));
    }

    /**
     * PUT /admin/events/{id} — actualiza un evento. 422 si la validación falla.
     */
    public function update(EventRequest $request, int $id): EventResource
    {
        return new EventResource($this->events->update($id, $request->validated()));
    }

    /**
     * DELETE /admin/events/{id} — soft-delete del evento.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->events->delete($id);

        return response()->json(['message' => 'Evento eliminado.']);
    }

    /**
     * POST /admin/events/{id}/restore — restaura un evento borrado.
     */
    public function restore(int $id): JsonResponse
    {
        $this->events->restore($id);

        return response()->json(['message' => 'Evento restaurado.']);
    }
}
