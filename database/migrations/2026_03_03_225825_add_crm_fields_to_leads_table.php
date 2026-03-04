<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('metros_cuadrados')->nullable()->after('url_propiedad');
            $table->integer('numero_recamaras')->nullable()->after('metros_cuadrados');
            $table->decimal('presupuesto', 12, 2)->nullable()->after('numero_recamaras');
            $table->string('localidades')->nullable()->after('presupuesto');
            $table->text('comentarios')->nullable()->after('localidades');
            $table->json('historial_acciones')->nullable()->after('comentarios');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn([
                'metros_cuadrados', 
                'numero_recamaras', 
                'presupuesto', 
                'localidades', 
                'comentarios', 
                'historial_acciones'
            ]);
        });
    }
};