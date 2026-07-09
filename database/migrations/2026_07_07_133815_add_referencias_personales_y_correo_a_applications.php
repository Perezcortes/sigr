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
            // Correo en las referencias comerciales que ya existían (solo tenían empresa/contacto/telefono)
            $table->string('referencia_comercial1_correo')->nullable()->after('referencia_comercial1_telefono');
            $table->string('referencia_comercial2_correo')->nullable()->after('referencia_comercial2_telefono');
            $table->string('referencia_comercial3_correo')->nullable()->after('referencia_comercial3_telefono');

            // Referencias Personales: no existían en absoluto para persona física
            $table->string('referencia_personal1_nombres')->nullable()->after('referencia_comercial3_correo');
            $table->string('referencia_personal1_telefono')->nullable()->after('referencia_personal1_nombres');
            $table->string('referencia_personal1_relacion')->nullable()->after('referencia_personal1_telefono');
            $table->string('referencia_personal1_correo')->nullable()->after('referencia_personal1_relacion');

            $table->string('referencia_personal2_nombres')->nullable()->after('referencia_personal1_correo');
            $table->string('referencia_personal2_telefono')->nullable()->after('referencia_personal2_nombres');
            $table->string('referencia_personal2_relacion')->nullable()->after('referencia_personal2_telefono');
            $table->string('referencia_personal2_correo')->nullable()->after('referencia_personal2_relacion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn([
                'referencia_comercial1_correo', 'referencia_comercial2_correo', 'referencia_comercial3_correo',
                'referencia_personal1_nombres', 'referencia_personal1_telefono', 'referencia_personal1_relacion', 'referencia_personal1_correo',
                'referencia_personal2_nombres', 'referencia_personal2_telefono', 'referencia_personal2_relacion', 'referencia_personal2_correo',
            ]);
        });
    }
};
