<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owner_requests', function (Blueprint $table) {
            $table->id();
            
            // Relaciones
            $table->foreignId('owner_id')->constrained()->onDelete('cascade');
            $table->foreignId('rent_id')->constrained()->onDelete('cascade');
            
            // Estatus de la solicitud
            $table->enum('estatus', ['nueva', 'en_proceso', 'completada', 'rechazada'])->default('nueva');
            
            // SECCIÓN: DATOS DEL PROPIETARIO (Persona Física)
            // Información Personal
            $table->string('nombres')->nullable();
            $table->string('primer_apellido')->nullable();
            $table->string('segundo_apellido')->nullable();
            $table->string('curp')->nullable();
            $table->string('email')->nullable();
            $table->string('telefono')->nullable();
            $table->enum('estado_civil', ['Casado', 'Divorciado', 'Soltero', 'Union libre'])->nullable();
            $table->enum('regimen_conyugal', ['Sociedad conyugal', 'Separacion de bienes'])->nullable();
            $table->enum('sexo', ['Masculino', 'Femenino'])->nullable();
            $table->enum('nacionalidad', ['Mexicana', 'Extranjera'])->nullable();
            $table->enum('tipo_identificacion', ['INE', 'Pasaporte'])->nullable();
            $table->string('rfc')->nullable();
            
            // Domicilio Actual
            $table->string('calle')->nullable();
            $table->string('numero_exterior')->nullable();
            $table->string('numero_interior')->nullable();
            $table->string('codigo_postal')->nullable();
            $table->string('colonia')->nullable();
            $table->string('delegacion_municipio')->nullable();
            $table->string('estado')->nullable();
            $table->text('referencias_ubicacion')->nullable();
            
            // Forma de Pago
            $table->enum('forma_pago', ['Efectivo', 'Transferencia', 'Cheque', 'Otro'])->nullable();
            $table->string('forma_pago_otro')->nullable();
            
            // Datos de Transferencia
            $table->string('titular_cuenta')->nullable();
            $table->string('numero_cuenta')->nullable();
            $table->string('nombre_banco')->nullable();
            $table->string('clabe_interbancaria')->nullable();
            
            // SECCIÓN: DATOS DEL INMUEBLE A ARRENDAR
            // Información del Inmueble
            $table->enum('tipo_inmueble', ['Casa', 'Departamento', 'Local comercial', 'Oficina', 'Bodega', 'Nave industrial', 'Consultorio', 'Terreno'])->nullable();
            $table->enum('uso_suelo', ['Habitacional', 'Comercial', 'Industrial'])->nullable();
            $table->enum('mascotas', ['si', 'no'])->nullable();
            $table->string('mascotas_especifica')->nullable();
            $table->decimal('precio_renta', 10, 2)->nullable();
            $table->enum('iva_renta', ['IVA incluido', 'Mas IVA', 'Sin IVA'])->nullable();
            $table->enum('frecuencia_pago', ['Mensual', 'Semanal', 'Quincenal', 'Semestral', 'Anual', 'Otra'])->nullable();
            $table->string('frecuencia_pago_otra')->nullable();
            $table->text('condiciones_pago')->nullable();
            $table->decimal('deposito_garantia', 10, 2)->nullable();
            $table->enum('paga_mantenimiento', ['si', 'no'])->nullable();
            $table->enum('quien_paga_mantenimiento', ['Arrendatario', 'Arrendador'])->nullable();
            $table->enum('mantenimiento_incluido_renta', ['si', 'no'])->nullable();
            $table->decimal('costo_mantenimiento_mensual', 10, 2)->nullable();
            $table->text('instrucciones_pago')->nullable();
            $table->enum('requiere_seguro', ['si', 'no'])->nullable();
            $table->string('cobertura_seguro')->nullable();
            $table->decimal('monto_cobertura_seguro', 10, 2)->nullable();
            $table->text('servicios_pagar')->nullable();
            
            // Dirección del Inmueble
            $table->string('inmueble_calle')->nullable();
            $table->string('inmueble_numero_exterior')->nullable();
            $table->string('inmueble_numero_interior')->nullable();
            $table->string('inmueble_codigo_postal')->nullable();
            $table->string('inmueble_colonia')->nullable();
            $table->string('inmueble_delegacion_municipio')->nullable();
            $table->string('inmueble_estado')->nullable();
            $table->text('inmueble_referencias')->nullable();
            $table->text('inmueble_inventario')->nullable();
            
            // SECCIÓN: REPRESENTACIÓN
            $table->enum('sera_representado', ['Si', 'No'])->nullable();
            $table->enum('tipo_representacion', ['Autorizacion para rentar', 'Mandato simple (carta poder)', 'Carta poder ratificada ante notario', 'Poder notarial'])->nullable();
            
            // Información del Representante
            $table->string('representante_nombres')->nullable();
            $table->string('representante_primer_apellido')->nullable();
            $table->string('representante_segundo_apellido')->nullable();
            $table->enum('representante_sexo', ['Masculino', 'Femenino'])->nullable();
            $table->string('representante_curp')->nullable();
            $table->enum('representante_tipo_identificacion', ['INE', 'Pasaporte'])->nullable();
            $table->string('representante_rfc')->nullable();
            $table->string('representante_telefono')->nullable();
            $table->string('representante_email')->nullable();
            
            // Domicilio del Representante
            $table->string('representante_calle')->nullable();
            $table->string('representante_numero_exterior')->nullable();
            $table->string('representante_numero_interior')->nullable();
            $table->string('representante_codigo_postal')->nullable();
            $table->string('representante_colonia')->nullable();
            $table->string('representante_delegacion_municipio')->nullable();
            $table->string('representante_estado')->nullable();
            $table->text('representante_referencias')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_requests');
    }
};