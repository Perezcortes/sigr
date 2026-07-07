<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Se agrega Edificio al catálogo (formulario comercial)
        DB::statement("ALTER TABLE tenant_requests MODIFY COLUMN tipo_inmueble ENUM('Casa','Departamento','Local comercial','Oficina','Bodega','Nave industrial','Consultorio','Terreno','Villa','Edificio') NULL");

        Schema::table('tenant_requests', function (Blueprint $table) {
            // Datos del negocio, solo aplica a solicitud comercial
            $table->string('ocupacion_actual')->nullable()->after('tipo_empleo');
            $table->integer('numero_empleados')->nullable()->after('ocupacion_actual');

            // 3 referencias comerciales x 3 campos nuevos c/u (el Figma pide 6 campos por referencia)
            $table->string('referencia_comercial1_puesto')->nullable()->after('referencia_comercial1_telefono');
            $table->string('referencia_comercial1_correo')->nullable()->after('referencia_comercial1_puesto');
            $table->string('referencia_comercial1_tiempo_conocerlo')->nullable()->after('referencia_comercial1_correo');

            $table->string('referencia_comercial2_puesto')->nullable()->after('referencia_comercial2_telefono');
            $table->string('referencia_comercial2_correo')->nullable()->after('referencia_comercial2_puesto');
            $table->string('referencia_comercial2_tiempo_conocerlo')->nullable()->after('referencia_comercial2_correo');

            $table->string('referencia_comercial3_puesto')->nullable()->after('referencia_comercial3_telefono');
            $table->string('referencia_comercial3_correo')->nullable()->after('referencia_comercial3_puesto');
            $table->string('referencia_comercial3_tiempo_conocerlo')->nullable()->after('referencia_comercial3_correo');
        });
    }

    public function down(): void
    {
        Schema::table('tenant_requests', function (Blueprint $table) {
            $table->dropColumn([
                'ocupacion_actual', 'numero_empleados',
                'referencia_comercial1_puesto', 'referencia_comercial1_correo', 'referencia_comercial1_tiempo_conocerlo',
                'referencia_comercial2_puesto', 'referencia_comercial2_correo', 'referencia_comercial2_tiempo_conocerlo',
                'referencia_comercial3_puesto', 'referencia_comercial3_correo', 'referencia_comercial3_tiempo_conocerlo',
            ]);
        });

        DB::statement("ALTER TABLE tenant_requests MODIFY COLUMN tipo_inmueble ENUM('Casa','Departamento','Local comercial','Oficina','Bodega','Nave industrial','Consultorio','Terreno','Villa') NULL");
    }
};
