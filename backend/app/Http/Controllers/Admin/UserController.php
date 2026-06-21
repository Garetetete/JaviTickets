<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminUserRequest;
use App\Http\Resources\AdminUserResource;
use App\Repositories\Contracts\AdminUserRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Panel admin: CRUD de usuarios admin/gate (rol admin). Soporta soft-delete/
 * restore y el parámetro ?with_trashed=1. Delega la persistencia en el repositorio.
 */
class UserController extends Controller
{
    public function __construct(
        private readonly AdminUserRepositoryInterface $users,
    ) {}

    /**
     * GET /admin/users — lista paginada de usuarios admin/gate (?role,
     * ?with_trashed=1, ?per_page).
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        return AdminUserResource::collection($this->users->paginate(
            filters: ['role' => $request->input('role')],
            perPage: (int) $request->integer('per_page', 20),
            withTrashed: $request->boolean('with_trashed'),
        ));
    }

    /**
     * POST /admin/users — crea un usuario admin/gate. Responde 201. 422 si la
     * validación falla.
     */
    public function store(AdminUserRequest $request): JsonResponse
    {
        $user = $this->users->create($request->validated());

        return (new AdminUserResource($user))->response()->setStatusCode(201);
    }

    /**
     * GET /admin/users/{id} — muestra un usuario. 404 si no existe.
     */
    public function show(int $id): AdminUserResource
    {
        return new AdminUserResource($this->users->find($id) ?? abort(404));
    }

    /**
     * PUT /admin/users/{id} — actualiza un usuario. La contraseña no se
     * sobreescribe si viene vacía/ausente. 422 si la validación falla.
     */
    public function update(AdminUserRequest $request, int $id): AdminUserResource
    {
        $data = $request->validated();

        // No sobreescribir la contraseña si viene vacía/ausente en update.
        if (empty($data['password'])) {
            unset($data['password']);
        }

        return new AdminUserResource($this->users->update($id, $data));
    }

    /**
     * DELETE /admin/users/{id} — soft-delete del usuario.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->users->delete($id);

        return response()->json(['message' => 'Usuario eliminado.']);
    }

    /**
     * POST /admin/users/{id}/restore — restaura un usuario borrado.
     */
    public function restore(int $id): JsonResponse
    {
        $this->users->restore($id);

        return response()->json(['message' => 'Usuario restaurado.']);
    }
}
