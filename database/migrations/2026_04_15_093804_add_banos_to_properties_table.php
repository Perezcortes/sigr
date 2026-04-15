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
        Schema::table('properties', function (Blueprint $table) {
            // Agregar 'recamaras' si no existe
            if (!Schema::hasColumn('properties', 'recamaras')) {
                $table->string('recamaras')->nullable();
            }
            // Agregar 'banos' si no existe
            if (!Schema::hasColumn('properties', 'banos')) {
                $table->string('banos')->nullable();
            }
            // Asegurar que 'metros_cuadrados' existe
            if (!Schema::hasColumn('properties', 'metros_cuadrados')) {
                $table->string('metros_cuadrados')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            if (Schema::hasColumn('properties', 'banos')) {
                $table->dropColumn('banos');
            }
            if (Schema::hasColumn('properties', 'recamaras')) {
                $table->dropColumn('recamaras');
            }
            if (Schema::hasColumn('properties', 'metros_cuadrados')) {
                $table->dropColumn('metros_cuadrados');
            }
        });
    }
};
