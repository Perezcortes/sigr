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
        if (!Schema::hasTable('properties')) {
            Schema::create('properties', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('folio')->unique();
                $table->enum('estatus', ['disponible', 'rentada', 'inactiva'])->default('disponible');
                
                // Datos del Inmueble
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
                $table->string('calle')->nullable();
                $table->string('numero_exterior')->nullable();
                $table->string('numero_interior')->nullable();
                $table->string('codigo_postal')->nullable();
                $table->string('colonia')->nullable();
                $table->string('delegacion_municipio')->nullable();
                $table->string('estado')->nullable();
                $table->text('referencias_ubicacion')->nullable();
                $table->text('inventario')->nullable();
                
                $table->timestamps();
            });
        } else {
            // Si la tabla ya existe, agregar las columnas faltantes
            Schema::table('properties', function (Blueprint $table) {
                // Agregar folio si no existe
                if (!Schema::hasColumn('properties', 'folio')) {
                    $table->string('folio')->unique()->nullable()->after('user_id');
                }
                
                // Agregar campos de inmueble si no existen
                if (!Schema::hasColumn('properties', 'tipo_inmueble')) {
                    $table->enum('tipo_inmueble', ['Casa', 'Departamento', 'Local comercial', 'Oficina', 'Bodega', 'Nave industrial', 'Consultorio', 'Terreno'])->nullable();
                }
                if (!Schema::hasColumn('properties', 'uso_suelo')) {
                    $table->enum('uso_suelo', ['Habitacional', 'Comercial', 'Industrial'])->nullable();
                }
                if (!Schema::hasColumn('properties', 'mascotas')) {
                    $table->enum('mascotas', ['si', 'no'])->nullable();
                }
                if (!Schema::hasColumn('properties', 'mascotas_especifica')) {
                    $table->string('mascotas_especifica')->nullable();
                }
                if (!Schema::hasColumn('properties', 'iva_renta')) {
                    $table->enum('iva_renta', ['IVA incluido', 'Mas IVA', 'Sin IVA'])->nullable();
                }
                if (!Schema::hasColumn('properties', 'frecuencia_pago')) {
                    $table->enum('frecuencia_pago', ['Mensual', 'Semanal', 'Quincenal', 'Semestral', 'Anual', 'Otra'])->nullable();
                }
                if (!Schema::hasColumn('properties', 'frecuencia_pago_otra')) {
                    $table->string('frecuencia_pago_otra')->nullable();
                }
                if (!Schema::hasColumn('properties', 'condiciones_pago')) {
                    $table->text('condiciones_pago')->nullable();
                }
                if (!Schema::hasColumn('properties', 'deposito_garantia')) {
                    $table->decimal('deposito_garantia', 10, 2)->nullable();
                }
                if (!Schema::hasColumn('properties', 'paga_mantenimiento')) {
                    $table->enum('paga_mantenimiento', ['si', 'no'])->nullable();
                }
                if (!Schema::hasColumn('properties', 'quien_paga_mantenimiento')) {
                    $table->enum('quien_paga_mantenimiento', ['Arrendatario', 'Arrendador'])->nullable();
                }
                if (!Schema::hasColumn('properties', 'mantenimiento_incluido_renta')) {
                    $table->enum('mantenimiento_incluido_renta', ['si', 'no'])->nullable();
                }
                if (!Schema::hasColumn('properties', 'costo_mantenimiento_mensual')) {
                    $table->decimal('costo_mantenimiento_mensual', 10, 2)->nullable();
                }
                if (!Schema::hasColumn('properties', 'instrucciones_pago')) {
                    $table->text('instrucciones_pago')->nullable();
                }
                if (!Schema::hasColumn('properties', 'requiere_seguro')) {
                    $table->enum('requiere_seguro', ['si', 'no'])->nullable();
                }
                if (!Schema::hasColumn('properties', 'cobertura_seguro')) {
                    $table->string('cobertura_seguro')->nullable();
                }
                if (!Schema::hasColumn('properties', 'monto_cobertura_seguro')) {
                    $table->decimal('monto_cobertura_seguro', 10, 2)->nullable();
                }
                if (!Schema::hasColumn('properties', 'servicios_pagar')) {
                    $table->text('servicios_pagar')->nullable();
                }
                
                // Agregar campos de dirección si no existen (usando nombres sin prefijo inmueble_)
                if (!Schema::hasColumn('properties', 'calle')) {
                    $table->string('calle')->nullable();
                }
                if (!Schema::hasColumn('properties', 'referencias_ubicacion')) {
                    $table->text('referencias_ubicacion')->nullable();
                }
                if (!Schema::hasColumn('properties', 'inventario')) {
                    $table->text('inventario')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
