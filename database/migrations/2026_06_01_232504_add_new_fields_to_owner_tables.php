<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columnasNuevas = [
            'nacionalidad_especifica', 'pais_origen', 'fecha_vencimiento_tarjeta', 
            'nue', 'tipo_residencia', 'mismo_domicilio_fiscal', 'calle_fiscal', 
            'numero_exterior_fiscal', 'numero_interior_fiscal', 'codigo_postal_fiscal', 
            'colonia_fiscal', 'municipio_fiscal', 'estado_fiscal', 'metros_cuadrados', 'tipo_representacion_otro'
        ];

        // Revisa y borra en owner_requests si quedaron a la mitad
        Schema::table('owner_requests', function (Blueprint $table) use ($columnasNuevas) {
            foreach ($columnasNuevas as $columna) {
                if (Schema::hasColumn('owner_requests', $columna)) {
                    $table->dropColumn($columna);
                }
            }
        });

        // Revisa y borra en owners si quedaron a la mitad
        Schema::table('owners', function (Blueprint $table) use ($columnasNuevas) {
            foreach ($columnasNuevas as $columna) {
                if (Schema::hasColumn('owners', $columna)) {
                    $table->dropColumn($columna);
                }
            }
        });

        // ACTUALIZAR TABLA OWNER_REQUESTS 
        Schema::table('owner_requests', function (Blueprint $table) {
            $table->text('nacionalidad_especifica')->nullable();
            $table->text('pais_origen')->nullable();
            $table->date('fecha_vencimiento_tarjeta')->nullable();
            $table->text('nue')->nullable();
            $table->text('tipo_residencia')->nullable();
            
            $table->string('mismo_domicilio_fiscal', 10)->nullable();
            $table->text('calle_fiscal')->nullable();
            $table->text('numero_exterior_fiscal')->nullable();
            $table->text('numero_interior_fiscal')->nullable();
            $table->string('codigo_postal_fiscal', 10)->nullable();
            $table->text('colonia_fiscal')->nullable();
            $table->text('municipio_fiscal')->nullable();
            $table->text('estado_fiscal')->nullable();
            
            $table->integer('metros_cuadrados')->nullable();
            $table->text('tipo_representacion_otro')->nullable();

            $table->string('facultades_en_acta', 10)->nullable()->change();
        });

        // ACTUALIZAR TABLA OWNERS 
        Schema::table('owners', function (Blueprint $table) {
            $table->text('nacionalidad_especifica')->nullable();
            $table->text('pais_origen')->nullable();
            $table->date('fecha_vencimiento_tarjeta')->nullable();
            $table->text('nue')->nullable();
            $table->text('tipo_residencia')->nullable();

            $table->string('mismo_domicilio_fiscal', 10)->nullable();
            $table->text('calle_fiscal')->nullable();
            $table->text('numero_exterior_fiscal')->nullable();
            $table->text('numero_interior_fiscal')->nullable();
            $table->string('codigo_postal_fiscal', 10)->nullable();
            $table->text('colonia_fiscal')->nullable();
            $table->text('municipio_fiscal')->nullable();
            $table->text('estado_fiscal')->nullable();

            $table->integer('metros_cuadrados')->nullable();
            $table->text('tipo_representacion_otro')->nullable();
            
            $table->string('facultades_en_acta', 10)->nullable()->change();
        });
    }

    public function down(): void
    {
        $columnsToDrop = [
            'nacionalidad_especifica', 'pais_origen', 'fecha_vencimiento_tarjeta', 
            'nue', 'tipo_residencia', 'mismo_domicilio_fiscal', 'calle_fiscal', 
            'numero_exterior_fiscal', 'numero_interior_fiscal', 'codigo_postal_fiscal', 
            'colonia_fiscal', 'municipio_fiscal', 'estado_fiscal', 'metros_cuadrados', 'tipo_representacion_otro'
        ];

        Schema::table('owner_requests', function (Blueprint $table) use ($columnsToDrop) {
            $table->dropColumn($columnsToDrop);
            $table->boolean('facultades_en_acta')->default(0)->change();
        });

        Schema::table('owners', function (Blueprint $table) use ($columnsToDrop) {
            $table->dropColumn($columnsToDrop);
            $table->boolean('facultades_en_acta')->default(0)->change();
        });
    }
};