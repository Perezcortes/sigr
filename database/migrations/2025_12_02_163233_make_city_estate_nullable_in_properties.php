<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // En SQLite, necesitamos recrear la tabla para hacer nullable las foreign keys
            DB::statement('PRAGMA foreign_keys=off;');
            
            // Crear tabla temporal con city_id y estate_id nullable
            DB::statement('
                CREATE TABLE properties_new (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    tipo TEXT,
                    direccion TEXT,
                    numero_interior TEXT,
                    numero_exterior TEXT,
                    colonia TEXT,
                    delegacion_municipio TEXT,
                    codigo_postal TEXT,
                    city_id INTEGER NULL,
                    estate_id INTEGER NULL,
                    precio_renta NUMERIC,
                    precio_venta NUMERIC,
                    metros_cuadrados INTEGER,
                    recamaras INTEGER,
                    banos INTEGER,
                    estacionamientos INTEGER,
                    descripcion TEXT,
                    lat NUMERIC,
                    lng NUMERIC,
                    activo INTEGER,
                    created_at DATETIME,
                    updated_at DATETIME,
                    user_id INTEGER NOT NULL,
                    nombre TEXT,
                    estatus TEXT NOT NULL DEFAULT "disponible",
                    folio TEXT UNIQUE,
                    tipo_inmueble TEXT,
                    uso_suelo TEXT,
                    mascotas TEXT,
                    mascotas_especifica TEXT,
                    iva_renta TEXT,
                    frecuencia_pago TEXT,
                    frecuencia_pago_otra TEXT,
                    condiciones_pago TEXT,
                    deposito_garantia NUMERIC,
                    paga_mantenimiento TEXT,
                    quien_paga_mantenimiento TEXT,
                    mantenimiento_incluido_renta TEXT,
                    costo_mantenimiento_mensual NUMERIC,
                    instrucciones_pago TEXT,
                    requiere_seguro TEXT,
                    cobertura_seguro TEXT,
                    monto_cobertura_seguro NUMERIC,
                    servicios_pagar TEXT,
                    calle TEXT,
                    referencias_ubicacion TEXT,
                    inventario TEXT,
                    estado TEXT,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE RESTRICT,
                    FOREIGN KEY (estate_id) REFERENCES estates(id) ON DELETE RESTRICT
                )
            ');
            
            // Copiar datos de la tabla antigua a la nueva
            DB::statement('
                INSERT INTO properties_new 
                SELECT * FROM properties
            ');
            
            // Eliminar tabla antigua
            DB::statement('DROP TABLE properties');
            
            // Renombrar tabla nueva
            DB::statement('ALTER TABLE properties_new RENAME TO properties');
            
            DB::statement('PRAGMA foreign_keys=on;');
        } else {
            // Para MySQL/PostgreSQL
            Schema::table('properties', function (Blueprint $table) {
                $table->foreignId('city_id')->nullable()->change();
                $table->foreignId('estate_id')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No revertimos porque es complejo
    }
};
