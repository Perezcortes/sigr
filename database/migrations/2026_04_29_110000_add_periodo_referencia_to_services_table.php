<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (! Schema::hasColumn('services', 'periodo_referencia')) {
                $table->string('periodo_referencia', 7)->nullable()->after('mes_correspondiente');
            }
        });

        Schema::table('services', function (Blueprint $table) {
            // Evita duplicar el pago del mismo servicio para el mismo periodo.
            $table->unique(
                ['rent_id', 'payment_setting_id', 'periodo_referencia'],
                'services_rent_setting_period_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropUnique('services_rent_setting_period_unique');

            if (Schema::hasColumn('services', 'periodo_referencia')) {
                $table->dropColumn('periodo_referencia');
            }
        });
    }
};
