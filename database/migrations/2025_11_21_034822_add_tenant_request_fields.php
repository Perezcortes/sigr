<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_requests', function (Blueprint $table) {
            // Campos para tipo de persona
            $table->enum('tipo_persona', ['fisica', 'moral'])->default('fisica')->after('estatus');
            
            // Campos para Persona Física - Datos Personales
            $table->text('email_confirmacion')->nullable(); 
            $table->date('fecha_nacimiento')->nullable();
            
            // Campos para Datos del Cónyuge
            $table->text('conyuge_nombres')->nullable();
            $table->text('conyuge_primer_apellido')->nullable();
            $table->text('conyuge_segundo_apellido')->nullable();
            $table->text('conyuge_telefono')->nullable();
            
            // Campos para Domicilio Actual
            $table->enum('situacion_habitacional', ['Inquilino', 'Pension-Hotel', 'Con padres o familiares', 'Propietario pagando', 'Propietario liberado'])->nullable();
            $table->text('arrendador_actual_nombres')->nullable();
            $table->text('arrendador_actual_primer_apellido')->nullable();
            $table->text('arrendador_actual_segundo_apellido')->nullable();
            $table->text('arrendador_actual_telefono')->nullable();
            $table->decimal('renta_actual', 10, 2)->nullable();
            $table->text('ocupa_desde_ano')->nullable(); 
            
            // Campos para Datos de Empleo e Ingresos
            $table->text('profesion_oficio_puesto')->nullable();
            $table->enum('tipo_empleo', ['Dueño de negocio', 'Empresario', 'Independiente', 'Empleado', 'Comisionista', 'Jubilado'])->nullable();
            $table->text('telefono_empleo')->nullable();
            $table->text('extension_empleo')->nullable();
            $table->text('empresa_trabaja')->nullable();
            $table->text('calle_empleo')->nullable();
            $table->text('numero_exterior_empleo')->nullable();
            $table->text('numero_interior_empleo')->nullable();
            $table->text('codigo_postal_empleo')->nullable();
            $table->text('colonia_empleo')->nullable();
            $table->text('delegacion_municipio_empleo')->nullable();
            $table->text('estado_empleo')->nullable();
            $table->date('fecha_ingreso')->nullable();
            
            // Campos para Jefe Inmediato
            $table->text('jefe_nombres')->nullable();
            $table->text('jefe_primer_apellido')->nullable();
            $table->text('jefe_segundo_apellido')->nullable();
            $table->text('jefe_telefono')->nullable();
            $table->text('jefe_extension')->nullable();
            
            // Campos para Ingresos
            $table->decimal('ingreso_mensual_comprobable', 10, 2)->nullable();
            $table->decimal('ingreso_mensual_no_comprobable', 10, 2)->nullable();
            $table->integer('numero_personas_dependen')->nullable();
            $table->boolean('otra_persona_aporta')->default(false);
            $table->integer('numero_personas_aportan')->nullable();
            
            // Campos para Personas que Aportan
            $table->text('persona_aporta_nombres')->nullable();
            $table->text('persona_aporta_primer_apellido')->nullable();
            $table->text('persona_aporta_segundo_apellido')->nullable();
            $table->text('persona_aporta_parentesco')->nullable();
            $table->text('persona_aporta_telefono')->nullable();
            $table->text('persona_aporta_empresa')->nullable();
            $table->decimal('persona_aporta_ingreso_comprobable', 10, 2)->nullable();
            
            // Campos para Uso de Propiedad
            $table->enum('tipo_inmueble_desea', ['Local', 'Oficina', 'Consultorio', 'Bodega', 'Nave Industrial'])->nullable();
            $table->text('giro_negocio')->nullable();
            $table->text('experiencia_giro')->nullable();
            $table->text('propositos_arrendamiento')->nullable();
            $table->boolean('sustituye_otro_domicilio')->default(false);
            
            // Campos para Domicilio Anterior
            $table->text('domicilio_anterior_calle')->nullable();
            $table->text('domicilio_anterior_numero_exterior')->nullable();
            $table->text('domicilio_anterior_numero_interior')->nullable();
            $table->text('domicilio_anterior_codigo_postal')->nullable();
            $table->text('domicilio_anterior_colonia')->nullable();
            $table->text('domicilio_anterior_delegacion_municipio')->nullable();
            $table->text('domicilio_anterior_estado')->nullable();
            $table->text('motivo_cambio_domicilio')->nullable();
            
            // Campos para Referencias Personales
            $table->text('referencia_personal1_nombres')->nullable();
            $table->text('referencia_personal1_primer_apellido')->nullable();
            $table->text('referencia_personal1_segundo_apellido')->nullable();
            $table->text('referencia_personal1_relacion')->nullable();
            $table->text('referencia_personal1_telefono')->nullable();
            
            $table->text('referencia_personal2_nombres')->nullable();
            $table->text('referencia_personal2_primer_apellido')->nullable();
            $table->text('referencia_personal2_segundo_apellido')->nullable();
            $table->text('referencia_personal2_relacion')->nullable();
            $table->text('referencia_personal2_telefono')->nullable();
            
            // Campos para Referencias Familiares
            $table->text('referencia_familiar1_nombres')->nullable();
            $table->text('referencia_familiar1_primer_apellido')->nullable();
            $table->text('referencia_familiar1_segundo_apellido')->nullable();
            $table->text('referencia_familiar1_relacion')->nullable();
            $table->text('referencia_familiar1_telefono')->nullable();
            
            $table->text('referencia_familiar2_nombres')->nullable();
            $table->text('referencia_familiar2_primer_apellido')->nullable();
            $table->text('referencia_familiar2_segundo_apellido')->nullable();
            $table->text('referencia_familiar2_relacion')->nullable();
            $table->text('referencia_familiar2_telefono')->nullable();
            
            // Campos para Persona Moral
            $table->text('razon_social')->nullable();
            $table->text('dominio_internet')->nullable();
            $table->decimal('ingreso_mensual_promedio', 10, 2)->nullable();
            
            // Campos para Acta Constitutiva
            $table->text('notario_nombres')->nullable();
            $table->text('notario_primer_apellido')->nullable();
            $table->text('notario_segundo_apellido')->nullable();
            $table->text('numero_escritura')->nullable();
            $table->date('fecha_constitucion')->nullable();
            $table->text('notario_numero')->nullable();
            $table->text('ciudad_registro')->nullable();
            $table->text('estado_registro')->nullable();
            $table->text('numero_registro_inscripcion')->nullable();
            $table->text('giro_comercial')->nullable();
            
            // Campos para Apoderado Legal
            $table->text('apoderado_nombres')->nullable();
            $table->text('apoderado_primer_apellido')->nullable();
            $table->text('apoderado_segundo_apellido')->nullable();
            $table->enum('apoderado_sexo', ['Masculino', 'Femenino'])->nullable();
            $table->text('apoderado_telefono')->nullable();
            $table->text('apoderado_extension')->nullable();
            $table->text('apoderado_email')->nullable();
            $table->boolean('facultades_en_acta')->default(false);
            
            // Campos para Facultades en Acta
            $table->text('escritura_publica_numero')->nullable();
            $table->text('notario_numero_facultades')->nullable();
            $table->date('fecha_escritura_facultades')->nullable();
            $table->text('numero_inscripcion_registro_publico')->nullable();
            $table->text('ciudad_registro_facultades')->nullable();
            $table->text('estado_registro_facultades')->nullable();
            $table->date('fecha_inscripcion_facultades')->nullable();
            $table->enum('tipo_representacion', ['Administrador único', 'Presidente del consejo', 'Socio administrador', 'Gerente', 'Otro'])->nullable();
            $table->text('tipo_representacion_otro')->nullable();
            
            // Campos para Referencias Comerciales
            $table->text('referencia_comercial1_empresa')->nullable();
            $table->text('referencia_comercial1_contacto')->nullable();
            $table->text('referencia_comercial1_telefono')->nullable();
            
            $table->text('referencia_comercial2_empresa')->nullable();
            $table->text('referencia_comercial2_contacto')->nullable();
            $table->text('referencia_comercial2_telefono')->nullable();
            
            $table->text('referencia_comercial3_empresa')->nullable();
            $table->text('referencia_comercial3_contacto')->nullable();
            $table->text('referencia_comercial3_telefono')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tenant_requests', function (Blueprint $table) {
            // Eliminar todos los campos agregados
            $columns = [
                'tipo_persona', 'email_confirmacion', 'fecha_nacimiento',
                'conyuge_nombres', 'conyuge_primer_apellido', 'conyuge_segundo_apellido', 'conyuge_telefono',
                'situacion_habitacional', 'arrendador_actual_nombres', 'arrendador_actual_primer_apellido', 
                'arrendador_actual_segundo_apellido', 'arrendador_actual_telefono', 'renta_actual', 'ocupa_desde_ano',
                'profesion_oficio_puesto', 'tipo_empleo', 'telefono_empleo', 'extension_empleo', 'empresa_trabaja',
                'calle_empleo', 'numero_exterior_empleo', 'numero_interior_empleo', 'codigo_postal_empleo', 
                'colonia_empleo', 'delegacion_municipio_empleo', 'estado_empleo', 'fecha_ingreso',
                'jefe_nombres', 'jefe_primer_apellido', 'jefe_segundo_apellido', 'jefe_telefono', 'jefe_extension',
                'ingreso_mensual_comprobable', 'ingreso_mensual_no_comprobable', 'numero_personas_dependen',
                'otra_persona_aporta', 'numero_personas_aportan', 'persona_aporta_nombres', 'persona_aporta_primer_apellido',
                'persona_aporta_segundo_apellido', 'persona_aporta_parentesco', 'persona_aporta_telefono',
                'persona_aporta_empresa', 'persona_aporta_ingreso_comprobable', 'tipo_inmueble_desea', 'giro_negocio',
                'experiencia_giro', 'propositos_arrendamiento', 'sustituye_otro_domicilio', 'domicilio_anterior_calle',
                'domicilio_anterior_numero_exterior', 'domicilio_anterior_numero_interior', 'domicilio_anterior_codigo_postal',
                'domicilio_anterior_colonia', 'domicilio_anterior_delegacion_municipio', 'domicilio_anterior_estado',
                'motivo_cambio_domicilio', 'referencia_personal1_nombres', 'referencia_personal1_primer_apellido',
                'referencia_personal1_segundo_apellido', 'referencia_personal1_relacion', 'referencia_personal1_telefono',
                'referencia_personal2_nombres', 'referencia_personal2_primer_apellido', 'referencia_personal2_segundo_apellido',
                'referencia_personal2_relacion', 'referencia_personal2_telefono', 'referencia_familiar1_nombres',
                'referencia_familiar1_primer_apellido', 'referencia_familiar1_segundo_apellido', 'referencia_familiar1_relacion',
                'referencia_familiar1_telefono', 'referencia_familiar2_nombres', 'referencia_familiar2_primer_apellido',
                'referencia_familiar2_segundo_apellido', 'referencia_familiar2_relacion', 'referencia_familiar2_telefono',
                'razon_social', 'dominio_internet', 'ingreso_mensual_promedio', 'notario_nombres', 'notario_primer_apellido',
                'notario_segundo_apellido', 'numero_escritura', 'fecha_constitucion', 'notario_numero', 'ciudad_registro',
                'estado_registro', 'numero_registro_inscripcion', 'giro_comercial', 'apoderado_nombres', 'apoderado_primer_apellido',
                'apoderado_segundo_apellido', 'apoderado_sexo', 'apoderado_telefono', 'apoderado_extension', 'apoderado_email',
                'facultades_en_acta', 'escritura_publica_numero', 'notario_numero_facultades', 'fecha_escritura_facultades',
                'numero_inscripcion_registro_publico', 'ciudad_registro_facultades', 'estado_registro_facultades',
                'fecha_inscripcion_facultades', 'tipo_representacion', 'tipo_representacion_otro', 'referencia_comercial1_empresa',
                'referencia_comercial1_contacto', 'referencia_comercial1_telefono', 'referencia_comercial2_empresa',
                'referencia_comercial2_contacto', 'referencia_comercial2_telefono', 'referencia_comercial3_empresa',
                'referencia_comercial3_contacto', 'referencia_comercial3_telefono'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('tenant_requests', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};