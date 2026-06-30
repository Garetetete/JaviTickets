<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\AdminUserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

/**
 * Autenticación de la sesión admin/gate (guard JWT "admin").
 * El login es público; el resto de acciones requieren el token emitido.
 */
class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $auth,
    ) {}

    /**
     * POST /admin/login — autentica a un usuario admin/gate y devuelve el
     * token JWT. 401 si las credenciales son inválidas o el usuario está inactivo.
     */
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

    /**
     * GET /admin/me — devuelve el usuario autenticado a partir del token.
     */
    public function me(): AdminUserResource
    {
        return new AdminUserResource($this->auth->me());
    }

    /**
     * POST /admin/logout — invalida el token JWT actual.
     */
    public function logout(): JsonResponse
    {
        $this->auth->logout();

        return response()->json(['message' => 'Sesión cerrada.']);
    }

    /**
     * POST /admin/refresh — renueva el token JWT y devuelve uno nuevo.
     */
    public function refresh(): JsonResponse
    {
        return response()->json($this->auth->refresh());
    }
}
