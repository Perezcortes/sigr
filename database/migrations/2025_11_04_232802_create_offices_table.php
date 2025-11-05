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
        Schema::create('offices', function (Blueprint $table) {
            $table->id(); // id int [pk, increment]

            // --- Información Básica y Contacto ---
            $table->string('nombre', 255);
            $table->string('telefono', 20)->nullable(); // Se permiten nulos por si no hay teléfono
            $table->string('correo', 150)->unique()->nullable();
            $table->string('responsable', 100)->nullable();
            $table->string('clave', 50)->unique();

            // --- Estatus ---
            $table->boolean('estatus_actividad')->default(true); // Estatus por defecto activo
            $table->boolean('estatus_recibir_leads')->default(false);

            // --- Dirección ---
            $table->string('calle', 100);
            $table->string('numero_interior', 20)->nullable();
            $table->string('numero_exterior', 20);
            $table->string('colonia', 100);
            $table->string('delegacion_municipio', 100);
            $table->string('codigo_postal', 10)->nullable();

            // --- Claves Foráneas (Relaciones) ---
            // city_id int [ref: > cities.id]
            $table->foreignId('city_id')->constrained('cities')->onDelete('restrict');

            // estate_id int [ref: > estates.id]
            $table->foreignId('estate_id')->constrained('estates')->onDelete('restrict');

            // --- Geolocalización ---
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();

            // --- Timestamps y Soft Deletes ---
            $table->timestamps(); // created_at y updated_at
            $table->softDeletes(); // deleted_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offices');
    }
};
