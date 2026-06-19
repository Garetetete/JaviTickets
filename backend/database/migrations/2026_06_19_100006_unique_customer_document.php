<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Identidad única del comprador: una persona = un customer (con muchas
        // órdenes). Evita filas duplicadas para la misma identidad.
        DB::statement('
            CREATE UNIQUE INDEX customers_document_unique
            ON customers (document_type, document_number)
            WHERE deleted_at IS NULL
        ');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS customers_document_unique');
    }
};
