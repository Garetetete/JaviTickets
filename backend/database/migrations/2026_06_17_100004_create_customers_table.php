<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('second_name')->nullable();
            $table->string('last_name');
            $table->string('second_last_name')->nullable();
            $table->string('document_type')->nullable(); // dni, passport, ce...
            $table->string('document_number');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('city_residence')->nullable();
            $table->string('country_residence')->nullable();
            $table->date('birth_date')->nullable();
            $table->jsonb('metadata')->nullable(); // metadatos extra arbitrarios
            $table->timestamps();
            $table->softDeletes();

            $table->index('email');
            $table->index(['document_type', 'document_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
