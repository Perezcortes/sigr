<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rents', function (Blueprint $table) {
            $table->string('folio')->nullable();
            $table->string('sucursal')->nullable();
            $table->string('abogado')->nullable();
            $table->string('inmobiliaria')->nullable();
            $table->string('estatus')->default('nueva');
            $table->string('tipo_inmueble')->nullable();
            $table->string('tipo_poliza')->nullable();
            $table->decimal('renta', 10, 2)->nullable();
            $table->decimal('poliza', 10, 2)->nullable();
            $table->string('tiene_fiador')->default('no');
            $table->string('tipo_propiedad')->nullable();
            $table->string('calle')->nullable();
            $table->string('numero_exterior')->nullable();
            $table->string('numero_interior')->nullable();
            $table->text('referencias_ubicacion')->nullable();
            $table->string('colonia')->nullable();
            $table->string('municipio')->nullable();
            $table->string('estado')->nullable();
            $table->string('codigo_postal')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('rents', function (Blueprint $table) {
            $table->dropColumn([
                'folio', 'sucursal', 'abogado', 'inmobiliaria', 'estatus',
                'tipo_inmueble', 'tipo_poliza', 'renta', 'poliza', 'tiene_fiador',
                'tipo_propiedad', 'calle', 'numero_exterior', 'numero_interior',
                'referencias_ubicacion', 'colonia', 'municipio', 'estado', 'codigo_postal'
            ]);
        });
    }
};