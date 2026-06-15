<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_reminders', function (Blueprint $table) {
            if (! Schema::hasColumn('payment_reminders', 'direccion')) {
                $table->string('direccion')->default('antes')->after('dias_antes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payment_reminders', function (Blueprint $table) {
            if (Schema::hasColumn('payment_reminders', 'direccion')) {
                $table->dropColumn('direccion');
            }
        });
    }
};
