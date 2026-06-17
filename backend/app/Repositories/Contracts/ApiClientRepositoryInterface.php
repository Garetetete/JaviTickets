<?php

namespace App\Repositories\Contracts;

use App\Models\ApiClient;

interface ApiClientRepositoryInterface extends RepositoryInterface
{
    public function findByClientId(string $clientId): ?ApiClient;

    /**
     * Rota el secret: genera uno nuevo, guarda su hash y devuelve el secret
     * en claro UNA sola vez.
     *
     * @return array{client_id: string, client_secret: string}
     */
    public function rotateSecret(int $id): array;
}
