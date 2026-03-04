<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Añade columnas faltantes a services (por si la tabla existía con otra estructura).
     */
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (!Schema::hasColumn('services', 'tipo')) {
                $table->string('tipo')->after('rent_id');
            }
            if (!Schema::hasColumn('services', 'mes_correspondiente')) {
                $table->string('mes_correspondiente')->nullable()->after('tipo');
            }
            if (!Schema::hasColumn('services', 'fecha_pago')) {
                $table->date('fecha_pago')->nullable()->after('mes_correspondiente');
            }
            if (!Schema::hasColumn('services', 'monto')) {
                $table->decimal('monto', 10, 2)->default(0)->after('fecha_pago');
            }
            if (!Schema::hasColumn('services', 'forma_pago')) {
                $table->string('forma_pago')->default('efectivo')->after('monto');
            }
            if (!Schema::hasColumn('services', 'evidencia')) {
                $table->string('evidencia')->nullable()->after('forma_pago');
            }
            if (!Schema::hasColumn('services', 'observaciones')) {
                $table->text('observaciones')->nullable()->after('evidencia');
            }
            if (!Schema::hasColumn('services', 'estatus')) {
                $table->string('estatus')->default('pagado')->after('observaciones');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $columns = ['tipo', 'mes_correspondiente', 'fecha_pago', 'monto', 'forma_pago', 'evidencia', 'observaciones', 'estatus'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('services', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
