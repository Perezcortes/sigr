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
    Schema::create('services', function (Blueprint $table) {
        $table->id();
        // Relación con la Renta
        $table->foreignId('rent_id')->constrained('rents')->cascadeOnDelete();
        
        // Campos del formulario
        $table->string('tipo'); // gas, agua, luz, renta, mantenimiento
        $table->string('mes_correspondiente'); // Enero, Febrero...
        $table->date('fecha_pago');
        $table->decimal('monto', 10, 2);
        $table->string('forma_pago')->default('efectivo'); // efectivo, tarjeta...
        $table->string('evidencia')->nullable(); // Ruta de la foto/archivo
        $table->text('observaciones')->nullable();
        
        $table->string('estatus')->default('pagado'); // pagado, vencido, pendiente
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
