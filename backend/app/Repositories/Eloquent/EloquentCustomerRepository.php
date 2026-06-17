<?php

namespace App\Repositories\Eloquent;

use App\Models\Customer;
use App\Repositories\Contracts\CustomerRepositoryInterface;

class EloquentCustomerRepository extends EloquentRepository implements CustomerRepositoryInterface
{
    protected string $model = Customer::class;

    public function findByDocument(string $type, string $number): ?Customer
    {
        return Customer::query()
            ->where('document_type', $type)
            ->where('document_number', $number)
            ->first();
    }

    public function findByEmail(string $email): ?Customer
    {
        return Customer::query()->where('email', $email)->first();
    }

    public function firstOrCreate(array $data): Customer
    {
        // Match por documento (identidad estable); si no hay, por email.
        $match = ! empty($data['document_number'])
            ? [
                'document_type' => $data['document_type'] ?? null,
                'document_number' => $data['document_number'],
            ]
            : ['email' => $data['email']];

        return Customer::query()->firstOrCreate($match, $data);
    }
}
