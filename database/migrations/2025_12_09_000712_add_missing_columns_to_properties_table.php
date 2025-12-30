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
            // Agregamos 'nombre' si no existe
            if (!Schema::hasColumn('properties', 'nombre')) {
                $table->string('nombre')->nullable()->after('direccion');
            }
            
            // Agregamos 'activo' si no existe (veo que también lo usas en el modelo)
            if (!Schema::hasColumn('properties', 'activo')) {
                $table->boolean('activo')->default(true)->after('nombre');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn(['nombre', 'activo']);
        });
    }
};
