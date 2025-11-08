<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Helper para agregar columna solo si no existe
     */
    private function addColumnIfNotExists(string $column, callable $callback): void
    {
        if (!Schema::hasColumn('owners', $column)) {
            Schema::table('owners', $callback);
        }
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Verificar si user_id existe
        $hasUserId = Schema::hasColumn('owners', 'user_id');

        if ($hasUserId) {
            // Si existe, eliminar la foreign key primero
            Schema::table('owners', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });

            // Luego hacer user_id nullable
            Schema::table('owners', function (Blueprint $table) {
                $table->bigInteger('user_id')->unsigned()->nullable()->change();
            });

            // Volver a agregar la foreign key
            Schema::table('owners', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        } else {
            // Si no existe, agregarlo como nullable
            Schema::table('owners', function (Blueprint $table) {
                $table->bigInteger('user_id')->unsigned()->nullable()->after('id');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        // Campos base
        $this->addColumnIfNotExists('tipo_persona', function (Blueprint $table) {
            $table->string('tipo_persona')->after('id');
        });

        // Persona Física - Información Personal
        $this->addColumnIfNotExists('nombres', function (Blueprint $table) {
            $table->string('nombres')->nullable()->after('tipo_persona');
        });
        $this->addColumnIfNotExists('primer_apellido', function (Blueprint $table) {
            $table->string('primer_apellido')->nullable()->after('nombres');
        });
        $this->addColumnIfNotExists('segundo_apellido', function (Blueprint $table) {
            $table->string('segundo_apellido')->nullable()->after('primer_apellido');
        });
        $this->addColumnIfNotExists('curp', function (Blueprint $table) {
            $table->string('curp')->nullable()->after('segundo_apellido');
        });
        $this->addColumnIfNotExists('email', function (Blueprint $table) {
            $table->string('email')->unique()->nullable()->after('curp');
        });
        $this->addColumnIfNotExists('telefono', function (Blueprint $table) {
            $table->string('telefono')->nullable()->after('email');
        });
        $this->addColumnIfNotExists('estado_civil', function (Blueprint $table) {
            $table->string('estado_civil')->nullable()->after('telefono');
        });
        $this->addColumnIfNotExists('regimen_conyugal', function (Blueprint $table) {
            $table->string('regimen_conyugal')->nullable()->after('estado_civil');
        });
        $this->addColumnIfNotExists('sexo', function (Blueprint $table) {
            $table->string('sexo')->nullable()->after('regimen_conyugal');
        });
        $this->addColumnIfNotExists('nacionalidad', function (Blueprint $table) {
            $table->string('nacionalidad')->nullable()->after('sexo');
        });
        $this->addColumnIfNotExists('tipo_identificacion', function (Blueprint $table) {
            $table->string('tipo_identificacion')->nullable()->after('nacionalidad');
        });
        $this->addColumnIfNotExists('rfc', function (Blueprint $table) {
            $table->string('rfc')->nullable()->after('tipo_identificacion');
        });

        // Persona Física - Domicilio Actual
        $this->addColumnIfNotExists('calle', function (Blueprint $table) {
            $table->string('calle')->nullable()->after('rfc');
        });
        $this->addColumnIfNotExists('numero_exterior', function (Blueprint $table) {
            $table->string('numero_exterior')->nullable()->after('calle');
        });
        $this->addColumnIfNotExists('numero_interior', function (Blueprint $table) {
            $table->string('numero_interior')->nullable()->after('numero_exterior');
        });
        $this->addColumnIfNotExists('codigo_postal', function (Blueprint $table) {
            $table->string('codigo_postal')->nullable()->after('numero_interior');
        });
        $this->addColumnIfNotExists('colonia', function (Blueprint $table) {
            $table->string('colonia')->nullable()->after('codigo_postal');
        });
        $this->addColumnIfNotExists('delegacion_municipio', function (Blueprint $table) {
            $table->string('delegacion_municipio')->nullable()->after('colonia');
        });
        $this->addColumnIfNotExists('estado', function (Blueprint $table) {
            $table->string('estado')->nullable()->after('delegacion_municipio');
        });
        $this->addColumnIfNotExists('referencias_ubicacion', function (Blueprint $table) {
            $table->text('referencias_ubicacion')->nullable()->after('estado');
        });

        // Forma de Pago (compartido)
        $this->addColumnIfNotExists('forma_pago', function (Blueprint $table) {
            $table->string('forma_pago')->nullable()->after('referencias_ubicacion');
        });
        $this->addColumnIfNotExists('forma_pago_otro', function (Blueprint $table) {
            $table->string('forma_pago_otro')->nullable()->after('forma_pago');
        });

        // Datos de Transferencia (compartido)
        $this->addColumnIfNotExists('titular_cuenta', function (Blueprint $table) {
            $table->string('titular_cuenta')->nullable()->after('forma_pago_otro');
        });
        $this->addColumnIfNotExists('numero_cuenta', function (Blueprint $table) {
            $table->string('numero_cuenta')->nullable()->after('titular_cuenta');
        });
        $this->addColumnIfNotExists('nombre_banco', function (Blueprint $table) {
            $table->string('nombre_banco')->nullable()->after('numero_cuenta');
        });
        $this->addColumnIfNotExists('clabe_interbancaria', function (Blueprint $table) {
            $table->string('clabe_interbancaria')->nullable()->after('nombre_banco');
        });

        // Persona Física - Representación
        $this->addColumnIfNotExists('sera_representado', function (Blueprint $table) {
            $table->string('sera_representado')->nullable()->after('clabe_interbancaria');
        });
        $this->addColumnIfNotExists('tipo_representacion', function (Blueprint $table) {
            $table->string('tipo_representacion')->nullable()->after('sera_representado');
        });

        // Datos del Representante (Persona Física)
        $this->addColumnIfNotExists('representante_nombres', function (Blueprint $table) {
            $table->string('representante_nombres')->nullable()->after('tipo_representacion');
        });
        $this->addColumnIfNotExists('representante_primer_apellido', function (Blueprint $table) {
            $table->string('representante_primer_apellido')->nullable()->after('representante_nombres');
        });
        $this->addColumnIfNotExists('representante_segundo_apellido', function (Blueprint $table) {
            $table->string('representante_segundo_apellido')->nullable()->after('representante_primer_apellido');
        });
        $this->addColumnIfNotExists('representante_sexo', function (Blueprint $table) {
            $table->string('representante_sexo')->nullable()->after('representante_segundo_apellido');
        });
        $this->addColumnIfNotExists('representante_curp', function (Blueprint $table) {
            $table->string('representante_curp')->nullable()->after('representante_sexo');
        });
        $this->addColumnIfNotExists('representante_tipo_identificacion', function (Blueprint $table) {
            $table->string('representante_tipo_identificacion')->nullable()->after('representante_curp');
        });
        $this->addColumnIfNotExists('representante_rfc', function (Blueprint $table) {
            $table->string('representante_rfc')->nullable()->after('representante_tipo_identificacion');
        });
        $this->addColumnIfNotExists('representante_telefono', function (Blueprint $table) {
            $table->string('representante_telefono')->nullable()->after('representante_rfc');
        });
        $this->addColumnIfNotExists('representante_email', function (Blueprint $table) {
            $table->string('representante_email')->nullable()->after('representante_telefono');
        });
        $this->addColumnIfNotExists('representante_calle', function (Blueprint $table) {
            $table->string('representante_calle')->nullable()->after('representante_email');
        });
        $this->addColumnIfNotExists('representante_numero_exterior', function (Blueprint $table) {
            $table->string('representante_numero_exterior')->nullable()->after('representante_calle');
        });
        $this->addColumnIfNotExists('representante_numero_interior', function (Blueprint $table) {
            $table->string('representante_numero_interior')->nullable()->after('representante_numero_exterior');
        });
        $this->addColumnIfNotExists('representante_cp', function (Blueprint $table) {
            $table->string('representante_cp')->nullable()->after('representante_numero_interior');
        });
        $this->addColumnIfNotExists('representante_colonia', function (Blueprint $table) {
            $table->string('representante_colonia')->nullable()->after('representante_cp');
        });
        $this->addColumnIfNotExists('representante_municipio', function (Blueprint $table) {
            $table->string('representante_municipio')->nullable()->after('representante_colonia');
        });
        $this->addColumnIfNotExists('representante_estado', function (Blueprint $table) {
            $table->string('representante_estado')->nullable()->after('representante_municipio');
        });
        $this->addColumnIfNotExists('representante_referencias', function (Blueprint $table) {
            $table->text('representante_referencias')->nullable()->after('representante_estado');
        });

        // Persona Moral - Información de la Empresa
        $this->addColumnIfNotExists('razon_social', function (Blueprint $table) {
            $table->string('razon_social')->nullable()->after('tipo_persona');
        });

        // Persona Moral - Acta Constitutiva
        $this->addColumnIfNotExists('notario_nombres', function (Blueprint $table) {
            $table->string('notario_nombres')->nullable()->after('clabe_interbancaria');
        });
        $this->addColumnIfNotExists('notario_primer_apellido', function (Blueprint $table) {
            $table->string('notario_primer_apellido')->nullable()->after('notario_nombres');
        });
        $this->addColumnIfNotExists('notario_segundo_apellido', function (Blueprint $table) {
            $table->string('notario_segundo_apellido')->nullable()->after('notario_primer_apellido');
        });
        $this->addColumnIfNotExists('numero_escritura', function (Blueprint $table) {
            $table->string('numero_escritura')->nullable()->after('notario_segundo_apellido');
        });
        $this->addColumnIfNotExists('fecha_constitucion', function (Blueprint $table) {
            $table->date('fecha_constitucion')->nullable()->after('numero_escritura');
        });
        $this->addColumnIfNotExists('notario_numero', function (Blueprint $table) {
            $table->string('notario_numero')->nullable()->after('fecha_constitucion');
        });
        $this->addColumnIfNotExists('ciudad_registro', function (Blueprint $table) {
            $table->string('ciudad_registro')->nullable()->after('notario_numero');
        });
        $this->addColumnIfNotExists('estado_registro', function (Blueprint $table) {
            $table->string('estado_registro')->nullable()->after('ciudad_registro');
        });
        $this->addColumnIfNotExists('numero_registro_inscripcion', function (Blueprint $table) {
            $table->string('numero_registro_inscripcion')->nullable()->after('estado_registro');
        });
        $this->addColumnIfNotExists('giro_comercial', function (Blueprint $table) {
            $table->string('giro_comercial')->nullable()->after('numero_registro_inscripcion');
        });

        // Persona Moral - Apoderado Legal
        $this->addColumnIfNotExists('apoderado_nombres', function (Blueprint $table) {
            $table->string('apoderado_nombres')->nullable()->after('giro_comercial');
        });
        $this->addColumnIfNotExists('apoderado_primer_apellido', function (Blueprint $table) {
            $table->string('apoderado_primer_apellido')->nullable()->after('apoderado_nombres');
        });
        $this->addColumnIfNotExists('apoderado_segundo_apellido', function (Blueprint $table) {
            $table->string('apoderado_segundo_apellido')->nullable()->after('apoderado_primer_apellido');
        });
        $this->addColumnIfNotExists('apoderado_sexo', function (Blueprint $table) {
            $table->string('apoderado_sexo')->nullable()->after('apoderado_segundo_apellido');
        });
        $this->addColumnIfNotExists('apoderado_curp', function (Blueprint $table) {
            $table->string('apoderado_curp')->nullable()->after('apoderado_sexo');
        });
        $this->addColumnIfNotExists('apoderado_email', function (Blueprint $table) {
            $table->string('apoderado_email')->nullable()->after('apoderado_curp');
        });
        $this->addColumnIfNotExists('apoderado_telefono', function (Blueprint $table) {
            $table->string('apoderado_telefono')->nullable()->after('apoderado_email');
        });
        $this->addColumnIfNotExists('apoderado_calle', function (Blueprint $table) {
            $table->string('apoderado_calle')->nullable()->after('apoderado_telefono');
        });
        $this->addColumnIfNotExists('apoderado_numero_exterior', function (Blueprint $table) {
            $table->string('apoderado_numero_exterior')->nullable()->after('apoderado_calle');
        });
        $this->addColumnIfNotExists('apoderado_numero_interior', function (Blueprint $table) {
            $table->string('apoderado_numero_interior')->nullable()->after('apoderado_numero_exterior');
        });
        $this->addColumnIfNotExists('apoderado_cp', function (Blueprint $table) {
            $table->string('apoderado_cp')->nullable()->after('apoderado_numero_interior');
        });
        $this->addColumnIfNotExists('apoderado_colonia', function (Blueprint $table) {
            $table->string('apoderado_colonia')->nullable()->after('apoderado_cp');
        });
        $this->addColumnIfNotExists('apoderado_municipio', function (Blueprint $table) {
            $table->string('apoderado_municipio')->nullable()->after('apoderado_colonia');
        });
        $this->addColumnIfNotExists('apoderado_estado', function (Blueprint $table) {
            $table->string('apoderado_estado')->nullable()->after('apoderado_municipio');
        });
        $this->addColumnIfNotExists('facultades_en_acta', function (Blueprint $table) {
            $table->boolean('facultades_en_acta')->nullable()->after('apoderado_estado');
        });

        // Si facultades_en_acta = true
        $this->addColumnIfNotExists('escritura_publica_numero', function (Blueprint $table) {
            $table->string('escritura_publica_numero')->nullable()->after('facultades_en_acta');
        });
        $this->addColumnIfNotExists('notario_numero_facultades', function (Blueprint $table) {
            $table->string('notario_numero_facultades')->nullable()->after('escritura_publica_numero');
        });
        $this->addColumnIfNotExists('fecha_escritura_facultades', function (Blueprint $table) {
            $table->date('fecha_escritura_facultades')->nullable()->after('notario_numero_facultades');
        });
        $this->addColumnIfNotExists('numero_inscripcion_registro_publico', function (Blueprint $table) {
            $table->string('numero_inscripcion_registro_publico')->nullable()->after('fecha_escritura_facultades');
        });
        $this->addColumnIfNotExists('ciudad_registro_facultades', function (Blueprint $table) {
            $table->string('ciudad_registro_facultades')->nullable()->after('numero_inscripcion_registro_publico');
        });
        $this->addColumnIfNotExists('estado_registro_facultades', function (Blueprint $table) {
            $table->string('estado_registro_facultades')->nullable()->after('ciudad_registro_facultades');
        });
        $this->addColumnIfNotExists('tipo_representacion_moral', function (Blueprint $table) {
            $table->string('tipo_representacion_moral')->nullable()->after('estado_registro_facultades');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Verificar si user_id existe
        $hasUserId = Schema::hasColumn('owners', 'user_id');

        if ($hasUserId) {
            // Eliminar la foreign key
            Schema::table('owners', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });

            // Revertir user_id a not null (solo si existía antes)
            Schema::table('owners', function (Blueprint $table) {
                $table->bigInteger('user_id')->unsigned()->nullable(false)->change();
            });

            // Volver a agregar la foreign key
            Schema::table('owners', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        Schema::table('owners', function (Blueprint $table) {
            // Eliminar todos los campos agregados
            $table->dropColumn([
                'tipo_persona',
                'nombres',
                'primer_apellido',
                'segundo_apellido',
                'curp',
                'email',
                'telefono',
                'estado_civil',
                'regimen_conyugal',
                'sexo',
                'nacionalidad',
                'tipo_identificacion',
                'rfc',
                'calle',
                'numero_exterior',
                'numero_interior',
                'codigo_postal',
                'colonia',
                'delegacion_municipio',
                'estado',
                'referencias_ubicacion',
                'forma_pago',
                'forma_pago_otro',
                'titular_cuenta',
                'numero_cuenta',
                'nombre_banco',
                'clabe_interbancaria',
                'sera_representado',
                'tipo_representacion',
                'representante_nombres',
                'representante_primer_apellido',
                'representante_segundo_apellido',
                'representante_sexo',
                'representante_curp',
                'representante_tipo_identificacion',
                'representante_rfc',
                'representante_telefono',
                'representante_email',
                'representante_calle',
                'representante_numero_exterior',
                'representante_numero_interior',
                'representante_cp',
                'representante_colonia',
                'representante_municipio',
                'representante_estado',
                'representante_referencias',
                'razon_social',
                'notario_nombres',
                'notario_primer_apellido',
                'notario_segundo_apellido',
                'numero_escritura',
                'fecha_constitucion',
                'notario_numero',
                'ciudad_registro',
                'estado_registro',
                'numero_registro_inscripcion',
                'giro_comercial',
                'apoderado_nombres',
                'apoderado_primer_apellido',
                'apoderado_segundo_apellido',
                'apoderado_sexo',
                'apoderado_curp',
                'apoderado_email',
                'apoderado_telefono',
                'apoderado_calle',
                'apoderado_numero_exterior',
                'apoderado_numero_interior',
                'apoderado_cp',
                'apoderado_colonia',
                'apoderado_municipio',
                'apoderado_estado',
                'facultades_en_acta',
                'escritura_publica_numero',
                'notario_numero_facultades',
                'fecha_escritura_facultades',
                'numero_inscripcion_registro_publico',
                'ciudad_registro_facultades',
                'estado_registro_facultades',
                'tipo_representacion_moral',
            ]);
        });
    }
};
