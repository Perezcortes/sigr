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
            // Agregar columna estado si no existe
            if (!Schema::hasColumn('properties', 'estado')) {
                $table->string('estado')->nullable()->after('delegacion_municipio');
            }
            
            // Asegurar que numero_exterior existe (puede que ya exista pero verificar)
            if (!Schema::hasColumn('properties', 'numero_exterior')) {
                $table->string('numero_exterior')->nullable()->after('calle');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            if (Schema::hasColumn('properties', 'estado')) {
                $table->dropColumn('estado');
            }
            if (Schema::hasColumn('properties', 'numero_exterior')) {
                $table->dropColumn('numero_exterior');
            }
        });
    }
};
