<?php

namespace App\Repositories\Eloquent;

use App\Models\Customer;
use App\Repositories\Contracts\CustomerRepositoryInterface;

/**
 * Implementación Eloquent de {@see \App\Repositories\Contracts\CustomerRepositoryInterface}.
 * Es la ÚNICA capa autorizada a ejecutar consultas Eloquent sobre el modelo Customer.
 */
class EloquentCustomerRepository extends EloquentRepository implements CustomerRepositoryInterface
{
    /** Modelo Eloquent gestionado por este repositorio. */
    protected string $model = Customer::class;

    /**
     * {@inheritDoc}
     *
     * Consulta el primer Customer que coincida en document_type y document_number.
     */
    public function findByDocument(string $type, string $number): ?Customer
    {
        return Customer::query()
            ->where('document_type', $type)
            ->where('document_number', $number)
            ->first();
    }

    /**
     * {@inheritDoc}
     *
     * Consulta el primer Customer cuyo email coincida.
     */
    public function findByEmail(string $email): ?Customer
    {
        return Customer::query()->where('email', $email)->first();
    }

    /**
     * {@inheritDoc}
     *
     * Normaliza el documento y el email, y empareja por (document_type,
     * document_number) para reutilizar el comprador o crearlo si no existe.
     */
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
