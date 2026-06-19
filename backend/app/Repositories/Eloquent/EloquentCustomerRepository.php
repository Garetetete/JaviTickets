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
        // Identidad consolidada: una persona = un customer (con muchas órdenes).
        // Normalizamos y emparejamos por (document_type, document_number).
        $data['document_type'] = $data['document_type'] ?? 'unknown';
        if (isset($data['email'])) {
            $data['email'] = mb_strtolower(trim($data['email']));
        }

        return Customer::query()->firstOrCreate(
            [
                'document_type' => $data['document_type'],
                'document_number' => $data['document_number'],
            ],
            $data
        );
    }
}
