<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TicketTypeRequest;
use App\Http\Resources\TicketTypeResource;
use App\Repositories\Contracts\TicketTypeRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TicketTypeController extends Controller
{
    public function __construct(
        private readonly TicketTypeRepositoryInterface $ticketTypes,
    ) {}

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

    public function store(TicketTypeRequest $request): JsonResponse
    {
        $type = $this->ticketTypes->create($request->validated());

        return (new TicketTypeResource($type))->response()->setStatusCode(201);
    }

    public function show(int $id): TicketTypeResource
    {
        return new TicketTypeResource($this->ticketTypes->find($id) ?? abort(404));
    }

    public function update(TicketTypeRequest $request, int $id): TicketTypeResource
    {
        return new TicketTypeResource($this->ticketTypes->update($id, $request->validated()));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->ticketTypes->delete($id);

        return response()->json(['message' => 'Tipo de ticket eliminado.']);
    }

    public function restore(int $id): JsonResponse
    {
        $this->ticketTypes->restore($id);

        return response()->json(['message' => 'Tipo de ticket restaurado.']);
    }
}
