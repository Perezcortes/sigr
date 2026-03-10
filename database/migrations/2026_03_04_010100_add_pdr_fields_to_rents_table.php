<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rents', function (Blueprint $table) {
            if (!Schema::hasColumn('rents', 'start_date')) {
                $table->date('start_date')->nullable();
            }
            if (!Schema::hasColumn('rents', 'end_date')) {
                $table->date('end_date')->nullable();
            }
            
            $table->string('plazo_arrendamiento')->nullable();
            $table->date('fecha_firma')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('rents', function (Blueprint $table) {
            $table->dropColumn(['plazo_arrendamiento', 'fecha_firma']);
        });
    }
};