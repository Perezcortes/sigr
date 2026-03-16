<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payable_operations', function (Blueprint $table) {
            $table->id();
            // Para saber si viene de una Renta o una Venta
            $table->morphs('payable'); 
            
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // El agente que debe pagar
            $table->string('nombre_cliente');
            $table->date('fecha_firma')->nullable();
            $table->decimal('monto_operacion', 12, 2); // Monto de la renta o venta
            $table->decimal('monto_comision', 12, 2); // Lo que ganó el agente
            $table->decimal('regalia', 12, 2); // El 12% que debe pagar
            
            $table->enum('estatus', ['pendiente de pago', 'pagada'])->default('pendiente de pago');
            $table->timestamp('fecha_vencimiento')->nullable(); // Para calcular los 10 días de suspensión
            $table->timestamp('fecha_pago')->nullable(); 
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payable_operations');
    }
};