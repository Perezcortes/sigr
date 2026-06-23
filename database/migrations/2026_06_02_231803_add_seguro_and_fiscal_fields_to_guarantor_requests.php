<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Columnas VARCHAR amplias que pasan a TEXT para reducir el tamaño de fila InnoDB.
     *
     * @var list<string>
     */
    private array $wideVarcharToText = [
        'estatus', 'tipo_figura', 'nombres', 'primer_apellido', 'segundo_apellido',
        'nacionalidad_especifica', 'relacion_solicitante', 'tiempo_conocerlo',
        'calle', 'colonia', 'municipio', 'estado',
        'fiscal_calle', 'fiscal_numero_exterior', 'fiscal_numero_interior',
        'fiscal_colonia', 'fiscal_municipio', 'fiscal_estado',
        'empresa_trabaja', 'profesion_puesto', 'tipo_empleo', 'regimen_fiscal',
        'empresa_calle', 'empresa_colonia', 'empresa_municipio', 'empresa_estado',
        'razon_social', 'antiguedad_empresa',
        'notario_nombres', 'notario_apellidos', 'numero_escritura',
        'ciudad_registro', 'estado_registro', 'numero_inscripcion_pm', 'giro_comercial',
        'rep_nombres', 'rep_primer_apellido', 'rep_segundo_apellido', 'rep_calle', 'rep_colonia',
        'fac_escritura', 'fac_inscripcion', 'fac_ciudad', 'fac_estado',
        'fac_tipo_representacion', 'fac_representacion_otro',
        'garantia_calle', 'garantia_colonia', 'garantia_municipio', 'garantia_estado',
        'garantia_num_escritura', 'garantia_notario_nombres', 'garantia_notario_paterno',
        'garantia_notario_materno', 'garantia_lugar_notaria', 'garantia_rpp',
        'garantia_folio_real', 'garantia_boleta_predial',
        'conyuge_nombres', 'conyuge_primer_apellido', 'conyuge_segundo_apellido',
    ];

    /**
     * Nuevas columnas TEXT de esta migración.
     *
     * @var list<string>
     */
    private array $newTextColumns = [
        'pais_origen', 'nue', 'tipo_residencia',
    ];

    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->upWithSchemaBuilder();
            return;
        }

        DB::statement('SET SESSION innodb_strict_mode = 0;');
        DB::statement('ALTER TABLE guarantor_requests ROW_FORMAT=DYNAMIC;');

        foreach ($this->wideVarcharToText as $column) {
            $this->ensureTextColumn('guarantor_requests', $column);
        }

        foreach ($this->newTextColumns as $column) {
            $this->ensureTextColumn('guarantor_requests', $column);
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

        if (!Schema::hasColumn('guarantor_requests', 'fiscal_codigo_postal')) {
            DB::statement("ALTER TABLE guarantor_requests ADD fiscal_codigo_postal VARCHAR(10) NULL;");
        } else {
            DB::statement("ALTER TABLE guarantor_requests MODIFY fiscal_codigo_postal VARCHAR(10) NULL;");
        }

        DB::statement('SET SESSION innodb_strict_mode = 1;');
    }

    private function upWithSchemaBuilder(): void
    {
        Schema::table('guarantor_requests', function (Blueprint $table) {
            $textColumns = array_unique(array_merge($this->wideVarcharToText, $this->newTextColumns));

            foreach ($textColumns as $column) {
                if (!Schema::hasColumn('guarantor_requests', $column)) {
                    $table->text($column)->nullable();
                } else {
                    $table->text($column)->nullable()->change();
                }
            }

            if (!Schema::hasColumn('guarantor_requests', 'fecha_vencimiento_tarjeta')) {
                $table->date('fecha_vencimiento_tarjeta')->nullable();
            }

            if (!Schema::hasColumn('guarantor_requests', 'metros_cuadrados')) {
                $table->integer('metros_cuadrados')->nullable();
            }

            if (!Schema::hasColumn('guarantor_requests', 'es_domicilio_fiscal')) {
                $table->integer('es_domicilio_fiscal')->nullable()->comment('1 = Sí, 0 = No');
            }

            if (!Schema::hasColumn('guarantor_requests', 'fiscal_codigo_postal')) {
                $table->string('fiscal_codigo_postal', 10)->nullable();
            } else {
                $table->string('fiscal_codigo_postal', 10)->nullable()->change();
            }
        });
    }

    private function ensureTextColumn(string $table, string $column): void
    {
        if (!Schema::hasColumn($table, $column)) {
            DB::statement("ALTER TABLE {$table} ADD {$column} TEXT NULL;");
        } else {
            DB::statement("ALTER TABLE {$table} MODIFY {$column} TEXT NULL;");
        }
    }

    public function down(): void
    {
        
    }
};