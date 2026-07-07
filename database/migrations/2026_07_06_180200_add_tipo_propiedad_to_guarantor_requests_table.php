<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guarantor_requests', function (Blueprint $table) {
            // Tipo de propiedad en garantía: Residencial/Comercial/Mixta (string libre, sin enum en BD)
            $table->string('garantia_tipo_propiedad')->nullable()->after('garantia_estado');
        });
    }

    public function down(): void
    {
        Schema::table('guarantor_requests', function (Blueprint $table) {
            $table->dropColumn('garantia_tipo_propiedad');
        });
    }
};
