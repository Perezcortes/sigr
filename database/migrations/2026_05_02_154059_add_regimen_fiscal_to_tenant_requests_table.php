<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_requests', function (Blueprint $table) {
            // Solo lo agrega si no existe
            if (!Schema::hasColumn('tenant_requests', 'regimen_fiscal')) {
                $table->string('regimen_fiscal')->nullable()->after('estado_civil');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenant_requests', function (Blueprint $table) {
            if (Schema::hasColumn('tenant_requests', 'regimen_fiscal')) {
                $table->dropColumn('regimen_fiscal');
            }
        });
    }
};