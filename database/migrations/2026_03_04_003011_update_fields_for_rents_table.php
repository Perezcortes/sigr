<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rents', function (Blueprint $table) {
            // Eliminamos campos viejos
            $table->dropColumn(['tipo_poliza', 'poliza', 'abogado']);
            
            // Agregamos nuevos campos de comisiones
            $table->decimal('monto_comision', 12, 2)->nullable()->after('renta');
            $table->decimal('porcentaje_comision_principal', 5, 2)->default(100)->after('monto_comision');
            $table->json('comisiones_divididas')->nullable()->after('porcentaje_comision_principal');
        });
    }

    public function down(): void
    {
        Schema::table('rents', function (Blueprint $table) {
            $table->string('tipo_poliza')->nullable();
            $table->decimal('poliza', 12, 2)->nullable();
            $table->string('abogado')->nullable();
            
            $table->dropColumn(['monto_comision', 'porcentaje_comision_principal', 'comisiones_divididas']);
        });
    }
};