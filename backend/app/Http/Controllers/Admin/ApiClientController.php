<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApiClientRequest;
use App\Http\Resources\ApiClientResource;
use App\Repositories\Contracts\ApiClientRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ApiClientController extends Controller
{
    public function __construct(
        private readonly ApiClientRepositoryInterface $clients,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return ApiClientResource::collection($this->clients->paginate(
            filters: [],
            perPage: (int) $request->integer('per_page', 20),
            withTrashed: $request->boolean('with_trashed'),
        ));
    }

    /** Crea el cliente y devuelve el client_secret en claro UNA sola vez. */
    public function store(ApiClientRequest $request): JsonResponse
    {
        $secret = Str::random(48);

        $client = $this->clients->create($request->validated() + [
            'client_secret_hash' => Hash::make($secret),
        ]);

        return response()->json([
            'client' => new ApiClientResource($client),
            'client_secret' => $secret, // se muestra una sola vez
        ], 201);
    }

    public function show(int $id): ApiClientResource
    {
        return new ApiClientResource($this->clients->find($id) ?? abort(404));
    }

    public function update(ApiClientRequest $request, int $id): ApiClientResource
    {
        return new ApiClientResource($this->clients->update($id, $request->validated()));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->clients->delete($id);

        return response()->json(['message' => 'Cliente API eliminado.']);
    }

    public function restore(int $id): JsonResponse
    {
        $this->clients->restore($id);

        return response()->json(['message' => 'Cliente API restaurado.']);
    }

    public function rotateSecret(int $id): JsonResponse
    {
        return response()->json($this->clients->rotateSecret($id));
    }
}
