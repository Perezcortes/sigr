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
        Schema::table('applications', function (Blueprint $table) {
            // Referencias comerciales (para persona moral)
            $table->text('referencia_comercial1_empresa')->nullable()->after('motivo_cambio_domicilio');
            $table->text('referencia_comercial1_contacto')->nullable();
            $table->text('referencia_comercial1_telefono')->nullable();
            $table->text('referencia_comercial2_empresa')->nullable();
            $table->text('referencia_comercial2_contacto')->nullable();
            $table->text('referencia_comercial2_telefono')->nullable();
            $table->text('referencia_comercial3_empresa')->nullable();
            $table->text('referencia_comercial3_contacto')->nullable();
            $table->text('referencia_comercial3_telefono')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn([
                'referencia_comercial1_empresa',
                'referencia_comercial1_contacto',
                'referencia_comercial1_telefono',
                'referencia_comercial2_empresa',
                'referencia_comercial2_contacto',
                'referencia_comercial2_telefono',
                'referencia_comercial3_empresa',
                'referencia_comercial3_contacto',
                'referencia_comercial3_telefono',
            ]);
        });
    }
};
