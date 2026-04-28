<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tickets')) {
            return;
        }

        if (! Schema::hasColumn('tickets', 'evidencia')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->string('evidencia')->nullable()->after('descripcion');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('tickets')) {
            return;
        }

        if (Schema::hasColumn('tickets', 'evidencia')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->dropColumn('evidencia');
            });
        }
    }
};
