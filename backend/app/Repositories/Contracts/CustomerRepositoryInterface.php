<?php

namespace App\Repositories\Contracts;

use App\Models\Customer;

interface CustomerRepositoryInterface extends RepositoryInterface
{
    public function findByDocument(string $type, string $number): ?Customer;

    public function findByEmail(string $email): ?Customer;

    /** Reusa el comprador en compras repetidas (match por documento). */
    public function firstOrCreate(array $data): Customer;
}
