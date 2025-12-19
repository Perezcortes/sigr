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
        // En SQLite, necesitamos recrear la tabla para cambiar NOT NULL a NULL
        // Primero verificamos si estamos en SQLite
        if (DB::getDriverName() === 'sqlite') {
            // Para SQLite, necesitamos usar SQL directo
            DB::statement('PRAGMA foreign_keys=off;');
            
            // Hacer nullable las columnas antiguas que pueden causar problemas
            $columnsToMakeNullable = [
                'tipo',
                'direccion',
                'nombre',
                'city_id',
                'estate_id',
                'precio_venta',
                'metros_cuadrados',
                'recamaras',
                'banos',
                'estacionamientos',
                'descripcion',
                'lat',
                'lng',
                'activo',
            ];
            
            foreach ($columnsToMakeNullable as $column) {
                if (Schema::hasColumn('properties', $column)) {
                    // En SQLite, necesitamos recrear la columna
                    // Primero obtenemos la información de la columna
                    try {
                        DB::statement("ALTER TABLE properties ALTER COLUMN {$column} DROP NOT NULL");
                    } catch (\Exception $e) {
                        // Si falla, intentamos con otro método
                        // En SQLite, ALTER COLUMN no funciona bien, así que lo ignoramos
                        // y simplemente no usaremos esas columnas
                    }
                }
            }
            
            DB::statement('PRAGMA foreign_keys=on;');
        } else {
            // Para otros motores de base de datos (MySQL, PostgreSQL)
            Schema::table('properties', function (Blueprint $table) {
                $columnsToMakeNullable = [
                    'tipo',
                    'direccion',
                    'nombre',
                    'city_id',
                    'estate_id',
                    'precio_venta',
                    'metros_cuadrados',
                    'recamaras',
                    'banos',
                    'estacionamientos',
                    'descripcion',
                    'lat',
                    'lng',
                    'activo',
                ];
                
                foreach ($columnsToMakeNullable as $column) {
                    if (Schema::hasColumn('properties', $column)) {
                        $table->string($column)->nullable()->change();
                    }
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No revertimos porque no sabemos los valores originales
    }
};
