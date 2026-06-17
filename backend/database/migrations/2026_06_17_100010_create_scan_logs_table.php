<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Append-only: solo created_at (sin updated_at ni soft delete).
        Schema::create('scan_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code')->nullable(); // code decodificado (aunque sea inválido)
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('result', [
                'valid', 'already_used', 'invalid_signature',
                'not_paid', 'void', 'not_found', 'wrong_event',
            ]);
            $table->foreignId('scanned_by')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->string('ip')->nullable();
            $table->string('device')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['event_id', 'result']);
            $table->index('ticket_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scan_logs');
    }
};
