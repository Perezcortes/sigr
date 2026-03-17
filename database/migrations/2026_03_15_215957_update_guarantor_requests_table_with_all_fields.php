<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guarantor_requests', function (Blueprint $table) {
            
            $table->id();
            $table->timestamps();

            // Relación con Rent
            $table->foreignId('rent_id')->constrained('rents')->cascadeOnDelete();

            // Datos Base 
            $table->string('estatus', 50)->default('nueva');
            $table->string('tipo_persona', 20)->nullable();
            $table->string('tipo_figura', 50)->nullable(); 

            // 1. Personales (Física)
            $table->string('nombres', 100)->nullable();
            $table->string('primer_apellido', 80)->nullable();
            $table->string('segundo_apellido', 80)->nullable();
            $table->string('nacionalidad', 30)->nullable();
            $table->string('nacionalidad_especifica', 50)->nullable();
            $table->string('sexo', 20)->nullable();
            $table->string('estado_civil', 30)->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('tipo_identificacion', 50)->nullable();
            $table->string('curp', 18)->nullable();
            $table->string('rfc', 13)->nullable(); 
            $table->string('email', 100)->nullable(); 
            $table->string('email_confirmacion', 100)->nullable();
            $table->string('telefono_celular', 20)->nullable();
            $table->string('telefono_fijo', 20)->nullable();
            $table->string('relacion_solicitante', 80)->nullable();
            $table->string('tiempo_conocerlo', 50)->nullable();

            // Domicilio 
            $table->string('calle', 100)->nullable();
            $table->string('numero_exterior', 20)->nullable();
            $table->string('numero_interior', 20)->nullable();
            $table->string('codigo_postal', 10)->nullable();
            $table->string('colonia', 100)->nullable();
            $table->string('municipio', 100)->nullable();
            $table->string('estado', 50)->nullable();
            $table->boolean('es_domicilio_fiscal')->nullable();

            // Domicilio Fiscal 
            $table->string('fiscal_calle', 100)->nullable();
            $table->string('fiscal_numero_exterior', 20)->nullable();
            $table->string('fiscal_numero_interior', 20)->nullable();
            $table->string('fiscal_codigo_postal', 10)->nullable();
            $table->string('fiscal_colonia', 100)->nullable();
            $table->string('fiscal_municipio', 100)->nullable();
            $table->string('fiscal_estado', 50)->nullable();

            // 2. Empleo e Ingresos (Física)
            $table->string('empresa_trabaja', 100)->nullable();
            $table->date('fecha_ingreso_empleo')->nullable();
            $table->string('profesion_puesto', 100)->nullable();
            $table->string('tipo_empleo', 50)->nullable();
            $table->string('regimen_fiscal', 50)->nullable(); 
            $table->decimal('ingreso_mensual', 15, 2)->nullable(); 
            $table->string('empresa_calle', 100)->nullable();
            $table->string('empresa_numero_exterior', 20)->nullable();
            $table->string('empresa_numero_interior', 20)->nullable();
            $table->string('empresa_codigo_postal', 10)->nullable();
            $table->string('empresa_colonia', 100)->nullable();
            $table->string('empresa_municipio', 100)->nullable();
            $table->string('empresa_estado', 50)->nullable();
            $table->string('empresa_telefono', 20)->nullable();
            $table->string('empresa_extension', 10)->nullable();
            $table->boolean('autoriza_buro')->nullable();
            $table->boolean('acepta_protesta')->nullable();

            // 3. Empresa (Moral)
            $table->string('razon_social', 150)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('antiguedad_empresa', 50)->nullable();
            $table->text('actividades_empresa')->nullable();

            // 4. Acta Constitutiva (Moral)
            $table->string('notario_nombres', 100)->nullable();
            $table->string('notario_apellidos', 100)->nullable();
            $table->string('numero_escritura', 50)->nullable();
            $table->date('fecha_constitucion')->nullable();
            $table->string('notario_numero', 20)->nullable();
            $table->string('ciudad_registro', 100)->nullable();
            $table->string('estado_registro', 50)->nullable();
            $table->string('numero_inscripcion_pm', 50)->nullable();
            $table->string('giro_comercial', 100)->nullable();

            // 5. Representante Legal (Moral)
            $table->string('rep_nombres', 100)->nullable();
            $table->string('rep_primer_apellido', 80)->nullable();
            $table->string('rep_segundo_apellido', 80)->nullable();
            $table->string('rep_sexo', 20)->nullable();
            $table->string('rep_rfc', 13)->nullable();
            $table->string('rep_curp', 18)->nullable();
            $table->string('rep_email', 100)->nullable();
            $table->string('rep_telefono', 20)->nullable();
            $table->string('rep_calle', 100)->nullable();
            $table->string('rep_numero_exterior', 20)->nullable();
            $table->string('rep_codigo_postal', 10)->nullable();
            $table->string('rep_colonia', 100)->nullable();

            // 6. Facultades (Moral)
            $table->boolean('facultades_en_acta')->nullable();
            $table->string('fac_escritura', 50)->nullable();
            $table->string('fac_notario', 20)->nullable();
            $table->date('fac_fecha_escritura')->nullable();
            $table->string('fac_inscripcion', 50)->nullable();
            $table->date('fac_fecha_inscripcion')->nullable();
            $table->string('fac_ciudad', 100)->nullable();
            $table->string('fac_estado', 50)->nullable();
            $table->string('fac_tipo_representacion', 50)->nullable();
            $table->string('fac_representacion_otro', 100)->nullable();

            // 7. Propiedad en Garantía (Compartido)
            $table->string('garantia_calle', 100)->nullable();
            $table->string('garantia_numero_exterior', 20)->nullable();
            $table->string('garantia_numero_interior', 20)->nullable();
            $table->string('garantia_codigo_postal', 10)->nullable();
            $table->string('garantia_colonia', 100)->nullable();
            $table->string('garantia_municipio', 100)->nullable();
            $table->string('garantia_estado', 50)->nullable();
            $table->string('garantia_num_escritura', 50)->nullable();
            $table->date('garantia_fecha_escritura')->nullable();
            $table->string('garantia_notario_nombres', 100)->nullable();
            $table->string('garantia_notario_paterno', 80)->nullable();
            $table->string('garantia_notario_materno', 80)->nullable();
            $table->string('garantia_num_notaria', 20)->nullable();
            $table->string('garantia_lugar_notaria', 100)->nullable();
            $table->string('garantia_rpp', 100)->nullable();
            $table->string('garantia_folio_real', 50)->nullable();
            $table->date('garantia_fecha_rpp')->nullable();
            $table->string('garantia_boleta_predial', 50)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guarantor_requests');
    }
};