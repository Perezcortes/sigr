<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ninguna migración anterior llegó a crear estas columnas realmente (create_services_table no las
     * incluye, add_missing_columns_to_services_table no las contempla, y make_nombre_nullable_in_services_table
     * solo modifica 'nombre' si ya existe). El modelo Service y el panel Filament (ServicesRelationManager)
     * ya las usan como si existieran — sin esta migración, cualquier alta de Service falla con
     * "Unknown column 'nombre'".
     */
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (! Schema::hasColumn('services', 'nombre')) {
                $table->string('nombre')->nullable()->after('payment_setting_id');
            }
            if (! Schema::hasColumn('services', 'frecuencia')) {
                $table->string('frecuencia')->nullable()->after('tipo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (Schema::hasColumn('services', 'nombre')) {
                $table->dropColumn('nombre');
            }
            if (Schema::hasColumn('services', 'frecuencia')) {
                $table->dropColumn('frecuencia');
            }
        });
    }
};
