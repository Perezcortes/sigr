<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE properties MODIFY COLUMN tipo_inmueble ENUM('Casa','Departamento','Local comercial','Oficina','Bodega','Nave industrial','Consultorio','Terreno','Edificio') NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE properties MODIFY COLUMN tipo_inmueble ENUM('Casa','Departamento','Local comercial','Oficina','Bodega','Nave industrial','Consultorio','Terreno') NULL");
    }
};
