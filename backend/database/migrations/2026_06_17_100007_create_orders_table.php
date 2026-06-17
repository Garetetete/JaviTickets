<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('external_reference')->nullable()->unique(); // id de orden WP (idempotencia)
            $table->foreignId('api_client_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('payment_status', [
                'pending_payment', 'pending_verification', 'verified', 'rejected',
            ])->default('pending_payment');
            $table->string('payment_method')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->integer('quantity')->default(1);
            $table->foreignId('ticket_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->dateTime('verified_at')->nullable();
            $table->string('rejected_reason')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('payment_status');
            $table->index('event_id');
            $table->index('ticket_type_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
