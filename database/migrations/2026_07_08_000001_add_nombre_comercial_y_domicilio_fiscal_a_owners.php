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
        Schema::table('owners', function (Blueprint $table) {
            // Existía en el formulario de apprentas pero nunca se guardaba
            $table->text('nombre_comercial')->nullable()->after('razon_social');

            // Antes compartía columnas con "Dirección" (Domicilio Actual) y se pisaban entre sí
            $table->text('domicilio_fiscal_calle')->nullable()->after('giro_comercial');
            $table->text('domicilio_fiscal_numero')->nullable()->after('domicilio_fiscal_calle');
            $table->text('domicilio_fiscal_colonia')->nullable()->after('domicilio_fiscal_numero');
            $table->text('domicilio_fiscal_municipio')->nullable()->after('domicilio_fiscal_colonia');
            $table->text('domicilio_fiscal_ciudad')->nullable()->after('domicilio_fiscal_municipio');
            $table->text('domicilio_fiscal_estado')->nullable()->after('domicilio_fiscal_ciudad');
            $table->text('domicilio_fiscal_codigo_postal')->nullable()->after('domicilio_fiscal_estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('owners', function (Blueprint $table) {
            $table->dropColumn([
                'nombre_comercial',
                'domicilio_fiscal_calle', 'domicilio_fiscal_numero', 'domicilio_fiscal_colonia',
                'domicilio_fiscal_municipio', 'domicilio_fiscal_ciudad', 'domicilio_fiscal_estado',
                'domicilio_fiscal_codigo_postal',
            ]);
        });
    }
};
