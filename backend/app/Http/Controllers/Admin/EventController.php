<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EventRequest;
use App\Http\Resources\EventResource;
use App\Repositories\Contracts\EventRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EventController extends Controller
{
    public function __construct(
        private readonly EventRepositoryInterface $events,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return EventResource::collection($this->events->paginate(
            filters: ['tour_id' => $request->integer('tour_id') ?: null],
            perPage: (int) $request->integer('per_page', 20),
            withTrashed: $request->boolean('with_trashed'),
        ));
    }

    public function store(EventRequest $request): JsonResponse
    {
        $event = $this->events->create($request->validated());

        return (new EventResource($event))->response()->setStatusCode(201);
    }

    public function show(int $id): EventResource
    {
        return new EventResource($this->events->find($id) ?? abort(404));
    }

    public function update(EventRequest $request, int $id): EventResource
    {
        return new EventResource($this->events->update($id, $request->validated()));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->events->delete($id);

        return response()->json(['message' => 'Evento eliminado.']);
    }

    public function restore(int $id): JsonResponse
    {
        $this->events->restore($id);

        return response()->json(['message' => 'Evento restaurado.']);
    }
}
