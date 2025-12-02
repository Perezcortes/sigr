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
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('folio')->unique();
            $table->enum('estatus', ['pendiente', 'en_revision', 'activa', 'aprobada', 'rechazada', 'vencida'])->default('pendiente');
            
            // Campos de Empleo
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
            $table->text('jefe_nombres')->nullable();
            $table->text('jefe_primer_apellido')->nullable();
            $table->text('jefe_segundo_apellido')->nullable();
            $table->text('jefe_telefono')->nullable();
            $table->text('jefe_extension')->nullable();
            
            // Campos de Ingresos
            $table->decimal('ingreso_mensual_comprobable', 10, 2)->nullable();
            $table->decimal('ingreso_mensual_no_comprobable', 10, 2)->nullable();
            $table->integer('numero_personas_dependen')->nullable();
            $table->boolean('otra_persona_aporta')->default(false);
            $table->integer('numero_personas_aportan')->nullable();
            $table->text('persona_aporta_nombres')->nullable();
            $table->text('persona_aporta_primer_apellido')->nullable();
            $table->text('persona_aporta_segundo_apellido')->nullable();
            $table->text('persona_aporta_parentesco')->nullable();
            $table->text('persona_aporta_telefono')->nullable();
            $table->text('persona_aporta_empresa')->nullable();
            $table->decimal('persona_aporta_ingreso_comprobable', 10, 2)->nullable();
            
            // Campos de Uso de Propiedad
            $table->enum('tipo_inmueble_desea', ['Local', 'Oficina', 'Consultorio', 'Bodega', 'Nave Industrial'])->nullable();
            $table->text('giro_negocio')->nullable();
            $table->text('experiencia_giro')->nullable();
            $table->text('propositos_arrendamiento')->nullable();
            $table->boolean('sustituye_otro_domicilio')->default(false);
            $table->text('domicilio_anterior_calle')->nullable();
            $table->text('domicilio_anterior_numero_exterior')->nullable();
            $table->text('domicilio_anterior_numero_interior')->nullable();
            $table->text('domicilio_anterior_codigo_postal')->nullable();
            $table->text('domicilio_anterior_colonia')->nullable();
            $table->text('domicilio_anterior_delegacion_municipio')->nullable();
            $table->text('domicilio_anterior_estado')->nullable();
            $table->text('motivo_cambio_domicilio')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
