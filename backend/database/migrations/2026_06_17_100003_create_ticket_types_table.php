<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tour_id')->constrained()->cascadeOnDelete();
            // Override opcional por evento; null = aplica a todos los del tour.
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->string('slug');
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->integer('quota')->nullable(); // cupo por tipo (null = limitado solo por event.capacity)
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tour_id', 'event_id', 'slug']);
            $table->index('event_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_types');
    }
};
