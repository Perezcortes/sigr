<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_requests', function (Blueprint $table) {
            // Liga la solicitud a una Application antes de que exista un Rent
            $table->foreignId('application_id')->nullable()->unique()->after('id')
                ->constrained('applications')->cascadeOnDelete();

            // Plazo de renta, lo pedía el formulario y no existía
            $table->date('plazo_desde')->nullable()->after('inmueble_referencias');
            $table->date('plazo_hasta')->nullable()->after('plazo_desde');

            // Correo por referencia, el Figma lo pedía y no existía
            $table->string('referencia_familiar1_email')->nullable()->after('referencia_familiar1_telefono');
            $table->string('referencia_familiar2_email')->nullable()->after('referencia_familiar2_telefono');
            $table->string('referencia_personal1_email')->nullable()->after('referencia_personal1_telefono');
            $table->string('referencia_personal2_email')->nullable()->after('referencia_personal2_telefono');
        });

        // rent_id deja de ser obligatorio: la solicitud existe antes de que haya Rent
        DB::statement("ALTER TABLE tenant_requests MODIFY COLUMN rent_id BIGINT UNSIGNED NULL");
        // Se agrega Villa al catálogo (formulario residencial)
        DB::statement("ALTER TABLE tenant_requests MODIFY COLUMN tipo_inmueble ENUM('Casa','Departamento','Local comercial','Oficina','Bodega','Nave industrial','Consultorio','Terreno','Villa') NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE tenant_requests MODIFY COLUMN tipo_inmueble ENUM('Casa','Departamento','Local comercial','Oficina','Bodega','Nave industrial','Consultorio','Terreno') NULL");
        DB::statement("ALTER TABLE tenant_requests MODIFY COLUMN rent_id BIGINT UNSIGNED NOT NULL");

        Schema::table('tenant_requests', function (Blueprint $table) {
            $table->dropColumn([
                'plazo_desde', 'plazo_hasta',
                'referencia_familiar1_email', 'referencia_familiar2_email',
                'referencia_personal1_email', 'referencia_personal2_email',
            ]);
            $table->dropConstrainedForeignId('application_id');
        });
    }
};
