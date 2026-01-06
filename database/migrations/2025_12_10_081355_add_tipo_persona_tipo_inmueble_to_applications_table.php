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
        Schema::table('applications', function (Blueprint $table) {
            $table->enum('tipo_persona', ['fisica', 'moral'])->nullable()->after('user_id');
            $table->enum('tipo_inmueble', ['residencial', 'comercial'])->nullable()->after('tipo_persona');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn(['tipo_persona', 'tipo_inmueble']);
        });
    }
};
