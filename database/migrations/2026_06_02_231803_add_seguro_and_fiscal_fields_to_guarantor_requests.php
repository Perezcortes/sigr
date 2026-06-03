<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE guarantor_requests ROW_FORMAT=DYNAMIC;');

        $columnasText = [
            'nacionalidad_especifica', 'pais_origen', 'nue', 'tipo_residencia',
            'fiscal_calle', 'fiscal_numero_exterior', 'fiscal_numero_interior',
            'fiscal_colonia', 'fiscal_municipio', 'fiscal_estado'
        ];

        foreach ($columnasText as $col) {
            if (Schema::hasColumn('guarantor_requests', $col)) {
                DB::statement("ALTER TABLE guarantor_requests MODIFY {$col} TEXT NULL;");
            } else {
                DB::statement("ALTER TABLE guarantor_requests ADD {$col} TEXT NULL;");
            }
        }

        if (!Schema::hasColumn('guarantor_requests', 'fecha_vencimiento_tarjeta')) {
            DB::statement("ALTER TABLE guarantor_requests ADD fecha_vencimiento_tarjeta DATE NULL;");
        }

        if (!Schema::hasColumn('guarantor_requests', 'metros_cuadrados')) {
            DB::statement("ALTER TABLE guarantor_requests ADD metros_cuadrados INT NULL;");
        }

        if (!Schema::hasColumn('guarantor_requests', 'es_domicilio_fiscal')) {
            DB::statement("ALTER TABLE guarantor_requests ADD es_domicilio_fiscal INT NULL COMMENT '1 = Sí, 0 = No';");
        }

        if (Schema::hasColumn('guarantor_requests', 'fiscal_codigo_postal')) {
            DB::statement("ALTER TABLE guarantor_requests MODIFY fiscal_codigo_postal VARCHAR(10) NULL;");
        } else {
            DB::statement("ALTER TABLE guarantor_requests ADD fiscal_codigo_postal VARCHAR(10) NULL;");
        }
    }

    public function down(): void
    {

    }
};