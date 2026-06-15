<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rents', function (Blueprint $table) {
            $table->boolean('is_administrada_por_agente')->default(false);
            $table->integer('dia_cobro_renta')->nullable(); 
            $table->boolean('enviar_recordatorio_inquilino')->default(true);
            $table->boolean('enviar_recordatorio_propietario')->default(true);
            $table->text('notas_administracion')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('rents', function (Blueprint $table) {
            $table->dropColumn([
                'is_administrada_por_agente',
                'dia_cobro_renta',
                'enviar_recordatorio_inquilino',
                'enviar_recordatorio_propietario',
                'notas_administracion'
            ]);
        });
    }
};