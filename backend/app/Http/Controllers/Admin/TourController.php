<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TourRequest;
use App\Http\Resources\TourResource;
use App\Repositories\Contracts\TourRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Panel admin: CRUD de tours (rol admin). Soporta soft-delete/restore y el
 * parámetro ?with_trashed=1. Delega la persistencia en el repositorio.
 */
class TourController extends Controller
{
    public function __construct(
        private readonly TourRepositoryInterface $tours,
    ) {}

    /**
     * GET /admin/tours — lista paginada de tours (?with_trashed=1, ?per_page).
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        return TourResource::collection($this->tours->paginate(
            filters: [],
            perPage: (int) $request->integer('per_page', 20),
            withTrashed: $request->boolean('with_trashed'),
        ));
    }

    /**
     * POST /admin/tours — crea un tour. Responde 201. 422 si la validación falla.
     */
    public function store(TourRequest $request): JsonResponse
    {
        $tour = $this->tours->create($request->validated());

        return (new TourResource($tour))->response()->setStatusCode(201);
    }

    /**
     * GET /admin/tours/{id} — muestra un tour. 404 si no existe.
     */
    public function show(int $id): TourResource
    {
        return new TourResource($this->tours->find($id) ?? abort(404));
    }

    /**
     * PUT /admin/tours/{id} — actualiza un tour. 422 si la validación falla.
     */
    public function update(TourRequest $request, int $id): TourResource
    {
        return new TourResource($this->tours->update($id, $request->validated()));
    }

    /**
     * DELETE /admin/tours/{id} — soft-delete del tour.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->tours->delete($id);

        return response()->json(['message' => 'Tour eliminado.']);
    }

    /**
     * POST /admin/tours/{id}/restore — restaura un tour borrado.
     */
    public function restore(int $id): JsonResponse
    {
        $this->tours->restore($id);

        return response()->json(['message' => 'Tour restaurado.']);
    }
}
