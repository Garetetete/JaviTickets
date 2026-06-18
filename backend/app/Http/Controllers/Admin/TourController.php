<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TourRequest;
use App\Http\Resources\TourResource;
use App\Repositories\Contracts\TourRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TourController extends Controller
{
    public function __construct(
        private readonly TourRepositoryInterface $tours,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return TourResource::collection($this->tours->paginate(
            filters: [],
            perPage: (int) $request->integer('per_page', 20),
            withTrashed: $request->boolean('with_trashed'),
        ));
    }

    public function store(TourRequest $request): JsonResponse
    {
        $tour = $this->tours->create($request->validated());

        return (new TourResource($tour))->response()->setStatusCode(201);
    }

    public function show(int $id): TourResource
    {
        return new TourResource($this->tours->find($id) ?? abort(404));
    }

    public function update(TourRequest $request, int $id): TourResource
    {
        return new TourResource($this->tours->update($id, $request->validated()));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->tours->delete($id);

        return response()->json(['message' => 'Tour eliminado.']);
    }

    public function restore(int $id): JsonResponse
    {
        $this->tours->restore($id);

        return response()->json(['message' => 'Tour restaurado.']);
    }
}
