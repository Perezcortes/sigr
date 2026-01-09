<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Renombrar la tabla
        Schema::rename('referrals', 'leads');

        // Agregar columnas nuevas
        Schema::table('leads', function (Blueprint $table) {
            $table->text('mensaje')->nullable()->after('origen'); // Para 'ContactMessage'
            $table->string('etapa')->default('no_contactado')->after('status'); 
            $table->dropColumn('status');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('status')->default('nuevo');
            $table->dropColumn(['mensaje', 'etapa']);
        });
        Schema::rename('leads', 'referrals');
    }
};