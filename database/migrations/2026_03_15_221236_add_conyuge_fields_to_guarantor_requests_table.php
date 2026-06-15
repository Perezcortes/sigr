<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guarantor_requests', function (Blueprint $table) {
            $table->string('conyuge_nombres', 100)->nullable()->after('estado_civil');
            $table->string('conyuge_primer_apellido', 80)->nullable()->after('conyuge_nombres');
            $table->string('conyuge_segundo_apellido', 80)->nullable()->after('conyuge_primer_apellido');
            $table->string('conyuge_telefono', 20)->nullable()->after('conyuge_segundo_apellido');
        });
    }

    public function down(): void
    {
        Schema::table('guarantor_requests', function (Blueprint $table) {
            $table->dropColumn([
                'conyuge_nombres',
                'conyuge_primer_apellido',
                'conyuge_segundo_apellido',
                'conyuge_telefono'
            ]);
        });
    }
};