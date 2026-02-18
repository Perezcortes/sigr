<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * La columna 'nombre' en production es NOT NULL; el formulario de pagos no la envía.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('services', 'nombre')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            Schema::getConnection()->statement('ALTER TABLE services MODIFY nombre VARCHAR(255) NULL');
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('services', 'nombre')) {
            return;
        }
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            Schema::getConnection()->statement('ALTER TABLE services MODIFY nombre VARCHAR(255) NOT NULL');
        }
    }
};
