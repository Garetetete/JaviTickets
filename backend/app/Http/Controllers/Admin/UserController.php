<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminUserRequest;
use App\Http\Resources\AdminUserResource;
use App\Repositories\Contracts\AdminUserRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    public function __construct(
        private readonly AdminUserRepositoryInterface $users,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return AdminUserResource::collection($this->users->paginate(
            filters: ['role' => $request->input('role')],
            perPage: (int) $request->integer('per_page', 20),
            withTrashed: $request->boolean('with_trashed'),
        ));
    }

    public function store(AdminUserRequest $request): JsonResponse
    {
        $user = $this->users->create($request->validated());

        return (new AdminUserResource($user))->response()->setStatusCode(201);
    }

    public function show(int $id): AdminUserResource
    {
        return new AdminUserResource($this->users->find($id) ?? abort(404));
    }

    public function update(AdminUserRequest $request, int $id): AdminUserResource
    {
        $data = $request->validated();

        // No sobreescribir la contraseña si viene vacía/ausente en update.
        if (empty($data['password'])) {
            unset($data['password']);
        }

        return new AdminUserResource($this->users->update($id, $data));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->users->delete($id);

        return response()->json(['message' => 'Usuario eliminado.']);
    }

    public function restore(int $id): JsonResponse
    {
        $this->users->restore($id);

        return response()->json(['message' => 'Usuario restaurado.']);
    }
}
