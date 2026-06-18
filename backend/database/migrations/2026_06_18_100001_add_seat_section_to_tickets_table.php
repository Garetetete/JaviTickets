<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('section')->nullable()->after('status'); // zona/sección (eventos numerados)
            $table->string('seat')->nullable()->after('section');    // asiento/fila
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['section', 'seat']);
        });
    }
};
