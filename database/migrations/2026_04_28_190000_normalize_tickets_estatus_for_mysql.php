<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tickets')) {
            return;
        }

        DB::table('tickets')->where('estatus', 'sin_revisar')->update(['estatus' => 'nueva']);
        DB::table('tickets')->where('estatus', 'terminado')->update(['estatus' => 'completada']);

        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        // Evita 1265 cuando la columna es ENUM o VARCHAR demasiado corto para otros valores
        DB::statement("ALTER TABLE `tickets` MODIFY `estatus` VARCHAR(50) NOT NULL DEFAULT 'nueva'");
    }

    public function down(): void
    {
        // Sin reversión: los valores ya fueron normalizados
    }
};
