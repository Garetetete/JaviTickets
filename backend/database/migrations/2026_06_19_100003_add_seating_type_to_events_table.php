<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // general = admisión general (sin asientos) | seated = numerado.
            $table->enum('seating_type', ['general', 'seated'])->default('general')->after('capacity');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('seating_type');
        });
    }
};
