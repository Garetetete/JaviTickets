<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Inventario de asientos por evento (eventos numerados).
        Schema::create('seats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('section')->default('GENERAL'); // zona; not null para que el unique funcione
            $table->string('label');                        // etiqueta del asiento, ej. "A-14"
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['event_id', 'section', 'label']);
            $table->index('event_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seats');
    }
};
