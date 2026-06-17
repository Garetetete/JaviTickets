<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_clients', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // "Tienda WordPress Javi"
            $table->string('client_id')->unique();
            $table->string('client_secret_hash'); // hash del secret (nunca en claro)
            $table->string('webhook_secret')->nullable(); // HMAC para firmar/verificar webhooks
            $table->jsonb('scopes'); // ["orders:write","tickets:read"]
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_clients');
    }
};
