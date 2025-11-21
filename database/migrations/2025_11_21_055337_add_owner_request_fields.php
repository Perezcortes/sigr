<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('owner_requests', function (Blueprint $table) {
            // Campos para Persona Moral
            $table->text('razon_social')->nullable()->after('tipo_persona');
            $table->text('dominio_internet')->nullable()->after('razon_social');
            
            // Campos para Acta Constitutiva (Persona Moral)
            $table->text('notario_nombres')->nullable()->after('dominio_internet');
            $table->text('notario_primer_apellido')->nullable()->after('notario_nombres');
            $table->text('notario_segundo_apellido')->nullable()->after('notario_primer_apellido');
            $table->text('numero_escritura')->nullable()->after('notario_segundo_apellido');
            $table->date('fecha_constitucion')->nullable()->after('numero_escritura');
            $table->text('notario_numero')->nullable()->after('fecha_constitucion');
            $table->text('ciudad_registro')->nullable()->after('notario_numero');
            $table->text('estado_registro')->nullable()->after('ciudad_registro');
            $table->text('numero_registro_inscripcion')->nullable()->after('estado_registro');
            $table->text('giro_comercial')->nullable()->after('numero_registro_inscripcion');
            
            // Campos para Apoderado Legal (Persona Moral)
            $table->text('apoderado_nombres')->nullable()->after('giro_comercial');
            $table->text('apoderado_primer_apellido')->nullable()->after('apoderado_nombres');
            $table->text('apoderado_segundo_apellido')->nullable()->after('apoderado_primer_apellido');
            $table->enum('apoderado_sexo', ['Masculino', 'Femenino'])->nullable()->after('apoderado_segundo_apellido');
            $table->text('apoderado_curp')->nullable()->after('apoderado_sexo');
            $table->text('apoderado_email')->nullable()->after('apoderado_curp');
            $table->text('apoderado_telefono')->nullable()->after('apoderado_email');
            $table->text('apoderado_calle')->nullable()->after('apoderado_telefono');
            $table->text('apoderado_numero_exterior')->nullable()->after('apoderado_calle');
            $table->text('apoderado_numero_interior')->nullable()->after('apoderado_numero_exterior');
            $table->text('apoderado_cp')->nullable()->after('apoderado_numero_interior');
            $table->text('apoderado_colonia')->nullable()->after('apoderado_cp');
            $table->text('apoderado_municipio')->nullable()->after('apoderado_colonia');
            $table->text('apoderado_estado')->nullable()->after('apoderado_municipio');
            $table->boolean('facultades_en_acta')->default(false)->after('apoderado_estado');
            
            // Campos para Facultades en Acta (Persona Moral)
            $table->text('escritura_publica_numero')->nullable()->after('facultades_en_acta');
            $table->text('notario_numero_facultades')->nullable()->after('escritura_publica_numero');
            $table->date('fecha_escritura_facultades')->nullable()->after('notario_numero_facultades');
            $table->text('numero_inscripcion_registro_publico')->nullable()->after('fecha_escritura_facultades');
            $table->text('ciudad_registro_facultades')->nullable()->after('numero_inscripcion_registro_publico');
            $table->text('estado_registro_facultades')->nullable()->after('ciudad_registro_facultades');
            $table->enum('tipo_representacion_moral', ['Administrador único', 'Presidente del consejo', 'Socio administrador', 'Gerente', 'Otro'])->nullable()->after('estado_registro_facultades');
            $table->text('tipo_representacion_otro')->nullable()->after('tipo_representacion_moral');
        });
    }

    public function down(): void
    {
        Schema::table('owner_requests', function (Blueprint $table) {
            $columns = [
                'razon_social', 'dominio_internet', 'notario_nombres', 'notario_primer_apellido',
                'notario_segundo_apellido', 'numero_escritura', 'fecha_constitucion', 'notario_numero',
                'ciudad_registro', 'estado_registro', 'numero_registro_inscripcion', 'giro_comercial',
                'apoderado_nombres', 'apoderado_primer_apellido', 'apoderado_segundo_apellido',
                'apoderado_sexo', 'apoderado_curp', 'apoderado_email', 'apoderado_telefono',
                'apoderado_calle', 'apoderado_numero_exterior', 'apoderado_numero_interior',
                'apoderado_cp', 'apoderado_colonia', 'apoderado_municipio', 'apoderado_estado',
                'facultades_en_acta', 'escritura_publica_numero', 'notario_numero_facultades',
                'fecha_escritura_facultades', 'numero_inscripcion_registro_publico',
                'ciudad_registro_facultades', 'estado_registro_facultades', 'tipo_representacion_moral',
                'tipo_representacion_otro'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('owner_requests', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};