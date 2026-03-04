<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('leads', 'tipo_transaccion')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->renameColumn('tipo_transaccion', 'tipo_cliente');
            });
        }

        if (!Schema::hasColumn('leads', 'calificacion_lead')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->string('calificacion_lead')->nullable()->after('tipo_cliente');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('leads', 'tipo_cliente')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->renameColumn('tipo_cliente', 'tipo_transaccion');
            });
        }

        if (Schema::hasColumn('leads', 'calificacion_lead')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->dropColumn('calificacion_lead');
            });
        }
    }
};