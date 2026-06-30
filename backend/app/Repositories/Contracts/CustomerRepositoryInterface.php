<?php

namespace App\Repositories\Contracts;

use App\Models\Customer;

interface CustomerRepositoryInterface extends RepositoryInterface
{
    /**
     * Busca un comprador por tipo y número de documento. Null si no existe.
     */
    public function findByDocument(string $type, string $number): ?Customer;

    /**
     * Busca un comprador por email. Null si no existe.
     */
    public function findByEmail(string $email): ?Customer;

    /**
     * Reusa el comprador en compras repetidas (match por documento) o lo crea.
     *
     * @param  array<string, mixed>  $data
     */
    public function firstOrCreate(array $data): Customer;
}
