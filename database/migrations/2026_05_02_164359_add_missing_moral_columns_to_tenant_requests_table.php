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
        Schema::table('tenant_requests', function (Blueprint $table) {
            
            // Función auxiliar para agregar columnas solo si no existen
            $addColumnIfNotExists = function($tableName, $columnName, $callback) use ($table) {
                if (!Schema::hasColumn($tableName, $columnName)) {
                    $callback($table);
                }
            };

            $addColumnIfNotExists('tenant_requests', 'telefono', fn($t) => $t->string('telefono', 20)->nullable());
            $addColumnIfNotExists('tenant_requests', 'municipio', fn($t) => $t->string('municipio')->nullable());

            // Empresa
            $addColumnIfNotExists('tenant_requests', 'dominio_internet', fn($t) => $t->string('dominio_internet')->nullable());
            $addColumnIfNotExists('tenant_requests', 'razon_social', fn($t) => $t->string('razon_social')->nullable());
            $addColumnIfNotExists('tenant_requests', 'ingreso_mensual_promedio', fn($t) => $t->string('ingreso_mensual_promedio')->nullable());
            $addColumnIfNotExists('tenant_requests', 'referencias_ubicacion', fn($t) => $t->text('referencias_ubicacion')->nullable());

            // Acta Constitutiva
            $addColumnIfNotExists('tenant_requests', 'notario_nombres', fn($t) => $t->string('notario_nombres')->nullable());
            $addColumnIfNotExists('tenant_requests', 'notario_primer_apellido', fn($t) => $t->string('notario_primer_apellido')->nullable());
            $addColumnIfNotExists('tenant_requests', 'notario_segundo_apellido', fn($t) => $t->string('notario_segundo_apellido')->nullable());
            $addColumnIfNotExists('tenant_requests', 'numero_escritura', fn($t) => $t->string('numero_escritura')->nullable());
            $addColumnIfNotExists('tenant_requests', 'fecha_constitucion', fn($t) => $t->date('fecha_constitucion')->nullable());
            $addColumnIfNotExists('tenant_requests', 'notario_numero', fn($t) => $t->string('notario_numero')->nullable());
            $addColumnIfNotExists('tenant_requests', 'ciudad_registro', fn($t) => $t->string('ciudad_registro')->nullable());
            $addColumnIfNotExists('tenant_requests', 'estado_registro', fn($t) => $t->string('estado_registro')->nullable());
            $addColumnIfNotExists('tenant_requests', 'numero_registro_inscripcion', fn($t) => $t->string('numero_registro_inscripcion')->nullable());
            $addColumnIfNotExists('tenant_requests', 'giro_comercial', fn($t) => $t->string('giro_comercial')->nullable());

            // Apoderado Legal
            $addColumnIfNotExists('tenant_requests', 'apoderado_nombres', fn($t) => $t->string('apoderado_nombres')->nullable());
            $addColumnIfNotExists('tenant_requests', 'apoderado_primer_apellido', fn($t) => $t->string('apoderado_primer_apellido')->nullable());
            $addColumnIfNotExists('tenant_requests', 'apoderado_segundo_apellido', fn($t) => $t->string('apoderado_segundo_apellido')->nullable());
            $addColumnIfNotExists('tenant_requests', 'apoderado_sexo', fn($t) => $t->string('apoderado_sexo')->nullable());
            $addColumnIfNotExists('tenant_requests', 'apoderado_telefono', fn($t) => $t->string('apoderado_telefono', 20)->nullable());
            $addColumnIfNotExists('tenant_requests', 'apoderado_extension', fn($t) => $t->string('apoderado_extension')->nullable());
            $addColumnIfNotExists('tenant_requests', 'apoderado_email', fn($t) => $t->string('apoderado_email')->nullable());
            $addColumnIfNotExists('tenant_requests', 'facultades_en_acta', fn($t) => $t->boolean('facultades_en_acta')->default(false));

            // Facultades en Acta
            $addColumnIfNotExists('tenant_requests', 'escritura_publica_numero', fn($t) => $t->string('escritura_publica_numero')->nullable());
            $addColumnIfNotExists('tenant_requests', 'notario_numero_facultades', fn($t) => $t->string('notario_numero_facultades')->nullable());
            $addColumnIfNotExists('tenant_requests', 'fecha_escritura_facultades', fn($t) => $t->date('fecha_escritura_facultades')->nullable());
            $addColumnIfNotExists('tenant_requests', 'numero_inscripcion_registro_publico', fn($t) => $t->string('numero_inscripcion_registro_publico')->nullable());
            $addColumnIfNotExists('tenant_requests', 'ciudad_registro_facultades', fn($t) => $t->string('ciudad_registro_facultades')->nullable());
            $addColumnIfNotExists('tenant_requests', 'estado_registro_facultades', fn($t) => $t->string('estado_registro_facultades')->nullable());
            $addColumnIfNotExists('tenant_requests', 'fecha_inscripcion_facultades', fn($t) => $t->date('fecha_inscripcion_facultades')->nullable());
            $addColumnIfNotExists('tenant_requests', 'tipo_representacion', fn($t) => $t->string('tipo_representacion')->nullable());
            $addColumnIfNotExists('tenant_requests', 'tipo_representacion_otro', fn($t) => $t->string('tipo_representacion_otro')->nullable());

            // Referencias Comerciales
            $addColumnIfNotExists('tenant_requests', 'referencia_comercial1_empresa', fn($t) => $t->string('referencia_comercial1_empresa')->nullable());
            $addColumnIfNotExists('tenant_requests', 'referencia_comercial1_contacto', fn($t) => $t->string('referencia_comercial1_contacto')->nullable());
            $addColumnIfNotExists('tenant_requests', 'referencia_comercial1_telefono', fn($t) => $t->string('referencia_comercial1_telefono', 20)->nullable());
            $addColumnIfNotExists('tenant_requests', 'referencia_comercial2_empresa', fn($t) => $t->string('referencia_comercial2_empresa')->nullable());
            $addColumnIfNotExists('tenant_requests', 'referencia_comercial2_contacto', fn($t) => $t->string('referencia_comercial2_contacto')->nullable());
            $addColumnIfNotExists('tenant_requests', 'referencia_comercial2_telefono', fn($t) => $t->string('referencia_comercial2_telefono', 20)->nullable());
            $addColumnIfNotExists('tenant_requests', 'referencia_comercial3_empresa', fn($t) => $t->string('referencia_comercial3_empresa')->nullable());
            $addColumnIfNotExists('tenant_requests', 'referencia_comercial3_contacto', fn($t) => $t->string('referencia_comercial3_contacto')->nullable());
            $addColumnIfNotExists('tenant_requests', 'referencia_comercial3_telefono', fn($t) => $t->string('referencia_comercial3_telefono', 20)->nullable());
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenant_requests', function (Blueprint $table) {
            //
        });
    }
};
