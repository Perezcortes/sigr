<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('pais_origen')->nullable();
            $table->date('fecha_vencimiento_tarjeta')->nullable();
            $table->string('nue')->nullable();
            $table->string('tipo_residencia')->nullable(); // permanente o temporal
            $table->integer('metros_cuadrados')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'pais_origen',
                'fecha_vencimiento_tarjeta',
                'nue',
                'tipo_residencia',
                'metros_cuadrados'
            ]);
        });
    }
};
