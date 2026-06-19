<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // El valor cifrado (cast 'encrypted') es más largo que varchar(255).
        Schema::table('api_clients', function (Blueprint $table) {
            $table->text('webhook_secret')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('api_clients', function (Blueprint $table) {
            $table->string('webhook_secret')->nullable()->change();
        });
    }
};
