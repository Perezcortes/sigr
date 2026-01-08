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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            
            // === COLUMNAS PARA EL LISTADO ===
            $table->date('fecha_inicio')->default(now());
            $table->string('nombre_cliente_principal'); // Para mostrar rápido en la tabla
            $table->decimal('monto_operacion', 15, 2)->nullable();
            $table->string('estatus_operacion')->default('En búsqueda');
            $table->string('estatus_hipoteca')->default('N/A');
            $table->string('tipo_inmueble')->nullable();

            // === PESTAÑA 1: COMPRADOR ===
            // Datos Generales
            $table->string('comprador_nombres')->nullable();
            $table->string('comprador_ap_paterno')->nullable();
            $table->string('comprador_ap_materno')->nullable();
            $table->string('comprador_telefono')->nullable();
            $table->string('comprador_celular')->nullable();
            $table->string('comprador_email')->nullable();
            $table->date('comprador_fecha_nacimiento')->nullable();
            $table->string('comprador_rfc')->nullable();
            $table->string('comprador_curp')->nullable();
            // Dirección
            $table->string('comprador_calle')->nullable();
            $table->string('comprador_colonia')->nullable();
            $table->string('comprador_ciudad')->nullable();
            $table->string('comprador_estado')->nullable();
            $table->string('comprador_cp')->nullable();
            // Repetidor: Otros compradores
            $table->json('compradores_adicionales')->nullable(); 

            // Datos Económicos
            $table->string('comprador_actividad')->nullable(); // Empleado, etc.
            $table->string('comprador_empresa')->nullable();
            $table->decimal('comprador_ingresos', 15, 2)->nullable();
            $table->string('comprador_tipo_comprobacion')->nullable();
            // Repetidor: Otras actividades
            $table->json('comprador_actividades_adicionales')->nullable();

            // === PESTAÑA 2: VENDEDOR ===
            $table->string('vendedor_nombres')->nullable();
            $table->string('vendedor_ap_paterno')->nullable();
            $table->string('vendedor_ap_materno')->nullable();
            $table->string('vendedor_telefono')->nullable();
            $table->string('vendedor_celular')->nullable();
            $table->string('vendedor_email')->nullable();
            $table->date('vendedor_fecha_nacimiento')->nullable();
            $table->string('vendedor_rfc')->nullable();
            $table->string('vendedor_curp')->nullable();
            // Dirección Vendedor
            $table->string('vendedor_calle')->nullable();
            $table->string('vendedor_colonia')->nullable();
            $table->string('vendedor_ciudad')->nullable();
            $table->string('vendedor_estado')->nullable();
            $table->string('vendedor_cp')->nullable();
            // Repetidor: Otros vendedores
            $table->json('vendedores_adicionales')->nullable();

            // === PESTAÑA 3: OPERACIÓN ===
            // estatus_operacion ya está arriba
            $table->decimal('precio_lista', 15, 2)->nullable();
            // precio_pactado (monto_operacion) ya está arriba
            $table->decimal('comision_porcentaje', 5, 2)->nullable();
            $table->decimal('comision_monto', 15, 2)->nullable();
            $table->string('momento_pago_comision')->nullable(); // Al firmar, escriturar, mixto
            $table->string('notaria_numero')->nullable();
            $table->string('notaria_titular')->nullable();
            $table->date('fecha_probable_cierre')->nullable();
            // Log de comentarios
            $table->json('bitacora_operacion')->nullable();

            // === PESTAÑA 4: HIPOTECA ===
            $table->boolean('requiere_hipoteca')->default(false);
            // estatus_hipoteca ya está arriba
            $table->string('hipoteca_broker')->nullable();
            $table->string('hipoteca_banco')->nullable();
            $table->string('hipoteca_ejecutivo_nombre')->nullable();
            $table->string('hipoteca_ejecutivo_telefono')->nullable();
            $table->string('hipoteca_ejecutivo_email')->nullable();
            $table->decimal('hipoteca_monto_aprobado', 15, 2)->nullable();
            $table->text('hipoteca_comentarios')->nullable();
            // Repetidor: Otros bancos
            $table->json('hipoteca_bancos_adicionales')->nullable();

            // Auditoría
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Quién creó la venta
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
