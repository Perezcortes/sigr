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
            // Agregamos el Ingreso Mensual (si no existía ya)
            if (!Schema::hasColumn('tenant_requests', 'ingreso_mensual_promedio')) {
                $table->decimal('ingreso_mensual_promedio', 10, 2)->nullable()->after('telefono'); 
            }

            // Agregamos los campos del Domicilio Fiscal
            $table->string('mismo_domicilio_fiscal', 2)->nullable(); // Guardará 'Si' o 'No'
            $table->string('calle_fiscal', 200)->nullable();
            $table->string('numero_exterior_fiscal', 100)->nullable();
            $table->string('numero_interior_fiscal', 100)->nullable();
            $table->string('codigo_postal_fiscal', 5)->nullable();
            $table->string('colonia_fiscal', 100)->nullable();
            $table->string('municipio_fiscal', 100)->nullable();
            $table->string('estado_fiscal', 100)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenant_requests', function (Blueprint $table) {
            $table->dropColumn([
                'ingreso_mensual_promedio', 
                'mismo_domicilio_fiscal',
                'calle_fiscal',
                'numero_exterior_fiscal',
                'numero_interior_fiscal',
                'codigo_postal_fiscal',
                'colonia_fiscal',
                'municipio_fiscal',
                'estado_fiscal',
            ]);
        });
    }
};