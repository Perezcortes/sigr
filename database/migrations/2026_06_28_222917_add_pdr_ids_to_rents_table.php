<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rents', function (Blueprint $table) {
            // Agregamos las columnas como string (por si hay rentas viejas)
            // Las colocamos convenientemente después de las columnas locales
            $table->string('pdr_office_id')->nullable()->after('office_id');
            $table->string('pdr_asesor_id')->nullable()->after('asesor_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rents', function (Blueprint $table) {
            $table->dropColumn(['pdr_office_id', 'pdr_asesor_id']);
        });
    }
};