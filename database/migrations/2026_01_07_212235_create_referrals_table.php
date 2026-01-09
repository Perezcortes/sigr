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
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('correo')->nullable();
            $table->string('telefono')->nullable();
            $table->text('url_propiedad')->nullable();
            $table->string('origen')->nullable(); // Ej: 'App Movil'
            
            // Responsable opcional (Foreign Key a Users)
            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            
            // Campos de control
            $table->string('status')->default('nuevo'); // nuevo, contactado, descartado, venta
            $table->json('payload_original')->nullable(); // Para guardar el JSON crudo por si acaso
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
