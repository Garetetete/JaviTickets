<?php

namespace App\Repositories\Eloquent;

use App\Models\ApiClient;
use App\Repositories\Contracts\ApiClientRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EloquentApiClientRepository extends EloquentRepository implements ApiClientRepositoryInterface
{
    protected string $model = ApiClient::class;

    public function findByClientId(string $clientId): ?ApiClient
    {
        return ApiClient::query()->where('client_id', $clientId)->first();
    }

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
