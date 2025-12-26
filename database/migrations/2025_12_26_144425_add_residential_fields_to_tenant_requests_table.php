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
        Schema::table('tenant_requests', function (Blueprint $table) {
            // Campos para uso Residencial
            $table->integer('numero_adultos')->nullable();
            $table->string('nombre_adulto_1')->nullable();
            $table->string('nombre_adulto_2')->nullable();
            $table->string('nombre_adulto_3')->nullable();
            $table->string('nombre_adulto_4')->nullable();
            $table->boolean('tiene_menores')->default(false);
            $table->integer('cuantos_menores')->nullable();
            $table->boolean('tiene_mascotas')->default(false);
            $table->text('especificar_mascotas')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenant_requests', function (Blueprint $table) {
            $table->dropColumn([
                'numero_adultos', 'nombre_adulto_1', 'nombre_adulto_2', 
                'nombre_adulto_3', 'nombre_adulto_4', 'tiene_menores', 
                'cuantos_menores', 'tiene_mascotas', 'especificar_mascotas'
            ]);
        });
    }
};
