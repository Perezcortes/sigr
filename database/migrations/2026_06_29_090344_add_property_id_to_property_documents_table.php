<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Paso 1: agregar property_id nullable para poder hacer el backfill sin violar NOT NULL
        Schema::table('property_documents', function (Blueprint $table) {
            $table->foreignId('property_id')->nullable()->after('id')->constrained()->onDelete('cascade');
        });

        // Paso 2: poblar property_id desde la renta para todos los registros existentes
        DB::statement('
            UPDATE property_documents pd
            INNER JOIN rents r ON r.id = pd.rent_id
            SET pd.property_id = r.property_id
        ');

        // Paso 3: property_id NOT NULL, rent_id nullable (documentos de propiedad no requieren renta)
        Schema::table('property_documents', function (Blueprint $table) {
            $table->unsignedBigInteger('property_id')->nullable(false)->change();
            $table->unsignedBigInteger('rent_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('property_documents', function (Blueprint $table) {
            $table->dropForeign(['property_id']);
            $table->dropColumn('property_id');
            $table->unsignedBigInteger('rent_id')->nullable(false)->change();
        });
    }
};
