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

/**
 * Panel admin: CRUD de clientes API (máquinas que consumen la tienda).
 * Protegido por auth:admin + role:admin. Soporta soft-delete/restore,
 * el parámetro ?with_trashed=1 y la rotación de credenciales.
 */
class ApiClientController extends Controller
{
    public function __construct(
        private readonly ApiClientRepositoryInterface $clients,
    ) {}

    /**
     * GET /admin/api-clients — lista paginada de clientes API (?with_trashed=1, ?per_page).
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        return ApiClientResource::collection($this->clients->paginate(
            filters: [],
            perPage: (int) $request->integer('per_page', 20),
            withTrashed: $request->boolean('with_trashed'),
        ));
    }

    /**
     * POST /admin/api-clients — crea el cliente y devuelve el client_secret
     * en claro UNA sola vez (luego solo se guarda su hash). Responde 201.
     */
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

    /**
     * GET /admin/api-clients/{id} — muestra un cliente API. 404 si no existe.
     */
    public function show(int $id): ApiClientResource
    {
        return new ApiClientResource($this->clients->find($id) ?? abort(404));
    }

    /**
     * PUT /admin/api-clients/{id} — actualiza un cliente API. 422 si la
     * validación falla.
     */
    public function update(ApiClientRequest $request, int $id): ApiClientResource
    {
        return new ApiClientResource($this->clients->update($id, $request->validated()));
    }

    /**
     * DELETE /admin/api-clients/{id} — soft-delete del cliente API.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->clients->delete($id);

        return response()->json(['message' => 'Cliente API eliminado.']);
    }

    /**
     * POST /admin/api-clients/{id}/restore — restaura un cliente API borrado.
     */
    public function restore(int $id): JsonResponse
    {
        $this->clients->restore($id);

        return response()->json(['message' => 'Cliente API restaurado.']);
    }

    /**
     * POST /admin/api-clients/{id}/rotate-secret — regenera el client_secret
     * y lo devuelve en claro UNA sola vez.
     */
    public function rotateSecret(int $id): JsonResponse
    {
        return response()->json($this->clients->rotateSecret($id));
    }
}
