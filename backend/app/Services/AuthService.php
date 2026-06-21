<?php

namespace App\Services;

use App\Models\AdminUser;
use App\Repositories\Contracts\ApiClientRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTFactory;

/**
 * Emisión y validación de JWT.
 *  - Clientes máquina (la tienda): client-credentials con scopes.
 *  - Usuarios admin/gate: ver loginAdmin (Fase 6).
 */
class AuthService
{
    public function __construct(
        private readonly ApiClientRepositoryInterface $apiClients,
    ) {}

    /**
     * Client-credentials: valida client_id + secret y emite un JWT con scopes.
     *
     * @return array{access_token:string, token_type:string, expires_in:int, scopes:array<int,string>}|null
     *         null si las credenciales son inválidas
     */
    public function issueClientToken(string $clientId, string $clientSecret): ?array
    {
        $client = $this->apiClients->findByClientId($clientId);

        if ($client === null
            || ! $client->is_active
            || ! Hash::check($clientSecret, $client->client_secret_hash)) {
            return null;
        }

        $payload = JWTFactory::customClaims([
            'sub' => $client->client_id,
            'ctype' => 'client',
            'scopes' => $client->scopes ?? [],
        ])->make();

        $token = JWTAuth::encode($payload)->get();

        return [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => (int) config('jwt.ttl', 60) * 60,
            'scopes' => $client->scopes ?? [],
        ];
    }

    /**
     * Login de usuario admin/gate. Devuelve token + usuario, o null si falla.
     *
     * @return array{access_token:string, token_type:string, expires_in:int, user:AdminUser}|null
     */
    public function loginAdmin(string $email, string $password): ?array
    {
        $token = Auth::guard('admin')->attempt(['email' => $email, 'password' => $password]);

        if ($token === false) {
            return null;
        }

        /** @var AdminUser $user */
        $user = Auth::guard('admin')->user();

        if (! $user->is_active) {
            Auth::guard('admin')->logout();

            return null;
        }

        return [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => (int) config('jwt.ttl', 60) * 60,
            'user' => $user,
        ];
    }

    /**
     * Devuelve el usuario admin/gate autenticado en el guard `admin`, o null.
     */
    public function me(): ?AdminUser
    {
        /** @var AdminUser|null $user */
        $user = Auth::guard('admin')->user();

        return $user;
    }

    /**
     * Invalida el token del usuario admin/gate autenticado.
     */
    public function logout(): void
    {
        Auth::guard('admin')->logout();
    }

    /**
     * Renueva el token del usuario admin/gate autenticado.
     *
     * @return array{access_token:string, token_type:string, expires_in:int}
     */
    public function refresh(): array
    {
        $token = Auth::guard('admin')->refresh();

        return [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => (int) config('jwt.ttl', 60) * 60,
        ];
    }
}
