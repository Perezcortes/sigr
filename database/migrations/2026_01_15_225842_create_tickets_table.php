<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rent_id')->constrained('rents')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users'); // Quien reporta
            
            $table->string('titulo'); // Ej: Daño de ventanas, Plomería
            $table->text('descripcion')->nullable(); // Observaciones
            $table->string('estatus')->default('sin_revisar'); // sin_revisar, en_proceso, terminado
            $table->string('evidencia')->nullable(); // Foto
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
