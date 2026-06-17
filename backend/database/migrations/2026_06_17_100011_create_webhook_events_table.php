<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Append-only + idempotencia (external_event_id único).
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_client_id')->constrained()->cascadeOnDelete();
            $table->string('external_event_id')->unique(); // dedup de notificaciones
            $table->string('order_external_reference')->nullable();
            $table->jsonb('payload'); // cuerpo recibido (auditoría)
            $table->boolean('signature_valid')->default(false);
            $table->boolean('processed')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->index('order_external_reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
