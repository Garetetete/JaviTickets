<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique(); // ULID; base del qr_token
            $table->text('qr_token'); // token firmado embebido en el QR
            $table->smallInteger('key_version')->default(1); // versión de clave HMAC (rotación)
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['issued', 'active', 'used', 'void', 'expired'])->default('active');
            $table->dateTime('used_at')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->string('voided_reason')->nullable();
            $table->jsonb('metadata')->nullable(); // snapshot del titular al emitir
            $table->timestamps();
            $table->softDeletes();

            $table->index(['event_id', 'status']);
            $table->index('customer_id');
            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
