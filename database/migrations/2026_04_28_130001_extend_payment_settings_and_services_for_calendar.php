<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('payment_settings', 'dia_pago')) {
                $table->unsignedTinyInteger('dia_pago')->nullable()->after('frecuencia');
            }
            if (! Schema::hasColumn('payment_settings', 'meses_intervalo')) {
                $table->unsignedTinyInteger('meses_intervalo')->default(1)->after('dia_pago');
            }
            if (! Schema::hasColumn('payment_settings', 'fecha_limite_pago')) {
                $table->date('fecha_limite_pago')->nullable()->after('meses_intervalo');
            }
            if (! Schema::hasColumn('payment_settings', 'es_base_renta')) {
                $table->boolean('es_base_renta')->default(false)->after('activo');
            }
        });

        Schema::create('payment_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_setting_id')->constrained('payment_settings')->cascadeOnDelete();
            $table->unsignedSmallInteger('dias_antes')->default(1);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::table('services', function (Blueprint $table) {
            if (! Schema::hasColumn('services', 'payment_setting_id')) {
                $table->foreignId('payment_setting_id')->nullable()->after('rent_id')->constrained('payment_settings')->nullOnDelete();
            }
            if (! Schema::hasColumn('services', 'fecha_vencimiento')) {
                $table->date('fecha_vencimiento')->nullable()->after('fecha_pago');
            }
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (Schema::hasColumn('services', 'payment_setting_id')) {
                $table->dropConstrainedForeignId('payment_setting_id');
            }
            if (Schema::hasColumn('services', 'fecha_vencimiento')) {
                $table->dropColumn('fecha_vencimiento');
            }
        });

        Schema::dropIfExists('payment_reminders');

        Schema::table('payment_settings', function (Blueprint $table) {
            $columns = ['dia_pago', 'meses_intervalo', 'fecha_limite_pago', 'es_base_renta'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('payment_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
