<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\AdminUserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $auth,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->auth->loginAdmin(
            $request->validated('email'),
            $request->validated('password'),
        );

        if ($result === null) {
            return response()->json(['message' => 'Credenciales inválidas o usuario inactivo.'], 401);
        }

        return response()->json([
            'access_token' => $result['access_token'],
            'token_type' => $result['token_type'],
            'expires_in' => $result['expires_in'],
            'user' => new AdminUserResource($result['user']),
        ]);
    }

    public function me(): AdminUserResource
    {
        return new AdminUserResource($this->auth->me());
    }

    public function logout(): JsonResponse
    {
        $this->auth->logout();

        return response()->json(['message' => 'Sesión cerrada.']);
    }

    public function refresh(): JsonResponse
    {
        return response()->json($this->auth->refresh());
    }
}
