<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_requests', function (Blueprint $table) {
            $table->id();
            
            // Relaciones
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('rent_id')->constrained()->onDelete('cascade');
            
            // Estatus de la solicitud
            $table->enum('estatus', ['nueva', 'en_proceso', 'completada', 'rechazada'])->default('nueva');
            
            // SECCIÓN: DATOS DEL INQUILINO (Persona Física)
            // Información Personal
            $table->text('nombres')->nullable();
            $table->text('primer_apellido')->nullable();
            $table->text('segundo_apellido')->nullable();
            $table->text('curp')->nullable();
            $table->text('email')->nullable();
            $table->text('telefono_celular')->nullable();
            $table->text('telefono_fijo')->nullable();
            $table->enum('estado_civil', ['soltero', 'casado', 'divorciado', 'viudo', 'union_libre'])->nullable();
            $table->enum('sexo', ['masculino', 'femenino'])->nullable();
            $table->enum('nacionalidad', ['mexicana', 'extranjera'])->nullable();
            $table->text('nacionalidad_especifica')->nullable();
            $table->enum('tipo_identificacion', ['INE', 'Pasaporte'])->nullable();
            $table->text('rfc')->nullable();
            
            // Domicilio Actual
            $table->text('calle')->nullable();
            $table->text('numero_exterior')->nullable();
            $table->text('numero_interior')->nullable();
            $table->text('codigo_postal')->nullable();
            $table->text('colonia')->nullable();
            $table->text('delegacion_municipio')->nullable();
            $table->text('estado')->nullable();
            $table->text('referencias_ubicacion')->nullable();
            
            // Forma de Pago
            $table->enum('forma_pago', ['Efectivo', 'Transferencia', 'Cheque', 'Otro'])->nullable();
            $table->text('forma_pago_otro')->nullable();
            
            // Datos de Transferencia
            $table->text('titular_cuenta')->nullable();
            $table->text('numero_cuenta')->nullable();
            $table->text('nombre_banco')->nullable();
            $table->text('clabe_interbancaria')->nullable();
            
            // SECCIÓN: DATOS DEL INMUEBLE A ARRENDAR
            // Información del Inmueble
            $table->enum('tipo_inmueble', ['Casa', 'Departamento', 'Local comercial', 'Oficina', 'Bodega', 'Nave industrial', 'Consultorio', 'Terreno'])->nullable();
            $table->enum('uso_suelo', ['Habitacional', 'Comercial', 'Industrial'])->nullable();
            $table->enum('mascotas', ['si', 'no'])->nullable();
            $table->text('mascotas_especifica')->nullable();
            $table->decimal('precio_renta', 10, 2)->nullable();
            $table->enum('iva_renta', ['IVA incluido', 'Mas IVA', 'Sin IVA'])->nullable();
            $table->enum('frecuencia_pago', ['Mensual', 'Semanal', 'Quincenal', 'Semestral', 'Anual', 'Otra'])->nullable();
            $table->text('frecuencia_pago_otra')->nullable();
            $table->text('condiciones_pago')->nullable();
            $table->decimal('deposito_garantia', 10, 2)->nullable();
            $table->enum('paga_mantenimiento', ['si', 'no'])->nullable();
            $table->enum('quien_paga_mantenimiento', ['Arrendatario', 'Arrendador'])->nullable();
            $table->enum('mantenimiento_incluido_renta', ['si', 'no'])->nullable();
            $table->decimal('costo_mantenimiento_mensual', 10, 2)->nullable();
            $table->text('instrucciones_pago')->nullable();
            $table->enum('requiere_seguro', ['si', 'no'])->nullable();
            $table->text('cobertura_seguro')->nullable();
            $table->decimal('monto_cobertura_seguro', 10, 2)->nullable();
            $table->text('servicios_pagar')->nullable();
            
            // Dirección del Inmueble
            $table->text('inmueble_calle')->nullable();
            $table->text('inmueble_numero_exterior')->nullable();
            $table->text('inmueble_numero_interior')->nullable();
            $table->text('inmueble_codigo_postal')->nullable();
            $table->text('inmueble_colonia')->nullable();
            $table->text('inmueble_delegacion_municipio')->nullable();
            $table->text('inmueble_estado')->nullable();
            $table->text('inmueble_referencias')->nullable();
            $table->text('inmueble_inventario')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_requests');
    }
};