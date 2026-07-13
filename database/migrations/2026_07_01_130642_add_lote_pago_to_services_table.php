<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (! Schema::hasColumn('services', 'lote_pago')) {
                $table->uuid('lote_pago')->nullable()->after('payment_setting_id');
                $table->index('lote_pago');
            }
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (Schema::hasColumn('services', 'lote_pago')) {
                $table->dropIndex(['lote_pago']);
                $table->dropColumn('lote_pago');
            }
        });
    }
};
