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
            $table->foreignId('rent_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained(); // Quién lo creó (Inquilino, Owner o Asesor)
            $table->enum('tipo', ['peticion', 'incidencia']);
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->enum('estatus', ['nueva', 'en_proceso', 'completada'])->default('nueva');
            $table->text('comentarios_admin')->nullable(); // Respuesta del admin
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
