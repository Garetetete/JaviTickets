<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('seat_id')->nullable()->after('seat')->constrained('seats')->nullOnDelete();
        });

        // Anti-doble-venta: un asiento solo puede tener UN ticket vigente
        // (issued/active/used) por evento. Índice único parcial de PostgreSQL.
        DB::statement("
            CREATE UNIQUE INDEX tickets_event_seat_active_unique
            ON tickets (event_id, seat_id)
            WHERE seat_id IS NOT NULL
              AND deleted_at IS NULL
              AND status IN ('issued','active','used')
        ");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS tickets_event_seat_active_unique');

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('seat_id');
        });
    }
};
