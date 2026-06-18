<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientAuthController extends Controller
{
    public function __construct(
        private readonly AuthService $auth,
    ) {}

    /**
     * Client-credentials: la tienda obtiene un JWT con sus scopes.
     */
    public function token(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_id' => ['required', 'string'],
            'client_secret' => ['required', 'string'],
        ]);

        $token = $this->auth->issueClientToken($validated['client_id'], $validated['client_secret']);

        if ($token === null) {
            return response()->json(['message' => 'Credenciales inválidas.'], 401);
        }

        return response()->json($token);
    }
}
