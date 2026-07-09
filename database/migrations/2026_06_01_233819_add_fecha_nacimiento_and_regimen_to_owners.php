<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columnas = ['fecha_nacimiento', 'regimen_fiscal'];

        Schema::table('owner_requests', function (Blueprint $table) use ($columnas) {
            foreach ($columnas as $columna) {
                if (Schema::hasColumn('owner_requests', $columna)) {
                    $table->dropColumn($columna);
                }
            }
        });

        Schema::table('owners', function (Blueprint $table) use ($columnas) {
            foreach ($columnas as $columna) {
                if (Schema::hasColumn('owners', $columna)) {
                    $table->dropColumn($columna);
                }
            }
        });

        Schema::table('owner_requests', function (Blueprint $table) {
            $table->date('fecha_nacimiento')->nullable();
            $table->text('regimen_fiscal')->nullable(); 
        });

        Schema::table('owners', function (Blueprint $table) {
            $table->date('fecha_nacimiento')->nullable();
            $table->text('regimen_fiscal')->nullable(); 
        });
    }

    public function down(): void
    {
        Schema::table('owner_requests', function (Blueprint $table) {
            $table->dropColumn(['fecha_nacimiento', 'regimen_fiscal']);
        });

        Schema::table('owners', function (Blueprint $table) {
            $table->dropColumn(['fecha_nacimiento', 'regimen_fiscal']);
        });
    }
};