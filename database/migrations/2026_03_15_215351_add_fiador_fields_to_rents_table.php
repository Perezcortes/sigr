<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rents', function (Blueprint $table) {
            $table->string('fiador_tipo_persona')->nullable()->after('tiene_fiador');
            $table->string('fiador_tipo')->nullable()->after('fiador_tipo_persona');
            $table->string('fiador_nombres')->nullable()->after('fiador_tipo');
            $table->string('fiador_primer_apellido')->nullable()->after('fiador_nombres');
            $table->string('fiador_segundo_apellido')->nullable()->after('fiador_primer_apellido');
            $table->string('fiador_sexo')->nullable()->after('fiador_segundo_apellido');
            $table->string('fiador_razon_social')->nullable()->after('fiador_sexo');
            $table->string('fiador_rfc')->nullable()->after('fiador_razon_social');
            $table->string('fiador_email')->nullable()->after('fiador_rfc');
        });
    }

    public function down(): void
    {
        Schema::table('rents', function (Blueprint $table) {
            $table->dropColumn([
                'fiador_tipo_persona',
                'fiador_tipo',
                'fiador_nombres',
                'fiador_primer_apellido',
                'fiador_segundo_apellido',
                'fiador_sexo',
                'fiador_razon_social',
                'fiador_rfc',
                'fiador_email',
            ]);
        });
    }
};