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
        // Agregar a Tenants
        Schema::table('tenants', function (Blueprint $table) {
            $table->foreignId('asesor_id')
                ->nullable()
                ->after('user_id') // Para ordenarlo visualmente
                ->constrained('users')
                ->nullOnDelete();
        });

        // Agregar a Owners (Propietarios) 
        Schema::table('owners', function (Blueprint $table) {
            $table->foreignId('asesor_id')
                ->nullable()
                ->after('user_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
        $table->dropForeign(['asesor_id']);
        $table->dropColumn('asesor_id');
        });
        Schema::table('owners', function (Blueprint $table) {
            $table->dropForeign(['asesor_id']);
            $table->dropColumn('asesor_id');
        });
    }
};
