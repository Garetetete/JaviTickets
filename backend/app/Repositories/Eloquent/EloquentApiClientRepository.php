<?php

namespace App\Repositories\Eloquent;

use App\Models\ApiClient;
use App\Repositories\Contracts\ApiClientRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Implementación Eloquent de {@see \App\Repositories\Contracts\ApiClientRepositoryInterface}.
 * Es la ÚNICA capa autorizada a ejecutar consultas Eloquent sobre el modelo ApiClient.
 */
class EloquentApiClientRepository extends EloquentRepository implements ApiClientRepositoryInterface
{
    /** Modelo Eloquent gestionado por este repositorio. */
    protected string $model = ApiClient::class;

    /**
     * {@inheritDoc}
     *
     * Consulta el primer ApiClient cuyo client_id público coincida.
     */
    public function findByClientId(string $clientId): ?ApiClient
    {
        return ApiClient::query()->where('client_id', $clientId)->first();
    }

    /**
     * {@inheritDoc}
     *
     * Genera un secret aleatorio de 48 caracteres, persiste solo su hash y
     * devuelve el secret en claro para mostrarlo una única vez.
     */
    public function rotateSecret(int $id): array
    {
        $client = ApiClient::query()->findOrFail($id);

        $plainSecret = Str::random(48);
        $client->update(['client_secret_hash' => Hash::make($plainSecret)]);

        return [
            'client_id' => $client->client_id,
            'client_secret' => $plainSecret, // se muestra UNA sola vez
        ];
    }
}
