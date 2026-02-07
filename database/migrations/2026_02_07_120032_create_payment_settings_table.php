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
        Schema::create('payment_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rent_id')->constrained('rents')->cascadeOnDelete();

            $table->string('tipo'); // Agua, Luz, Renta...
            $table->string('frecuencia'); // Mensual, Bimestral...
            $table->decimal('monto', 10, 2)->nullable();
            $table->string('moneda')->default('MXN');
            $table->boolean('es_variable')->default(false);
            $table->string('recordatorio')->nullable(); // 10 dias antes, etc.
            $table->boolean('activo')->default(true); // El switch de activado/desactivado

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_settings');
    }
};
