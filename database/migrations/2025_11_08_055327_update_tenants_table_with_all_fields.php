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
        if (!Schema::hasColumn('tenants', $column)) {
            Schema::table('tenants', $callback);
        }
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Verificar si user_id existe
        $hasUserId = Schema::hasColumn('tenants', 'user_id');

        if ($hasUserId) {
            // Si existe, eliminar la foreign key primero
            Schema::table('tenants', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });

            // Luego hacer user_id nullable
            Schema::table('tenants', function (Blueprint $table) {
                $table->bigInteger('user_id')->unsigned()->nullable()->change();
            });

            // Volver a agregar la foreign key
            Schema::table('tenants', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        } else {
            // Si no existe, agregarlo como nullable
            Schema::table('tenants', function (Blueprint $table) {
                $table->bigInteger('user_id')->unsigned()->nullable()->after('id');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        // Campos base
        $this->addColumnIfNotExists('tipo_persona', function (Blueprint $table) {
            $table->string('tipo_persona')->after('id');
        });

        // Campos Persona Física
        $this->addColumnIfNotExists('nombres', function (Blueprint $table) {
            $table->string('nombres')->nullable()->after('tipo_persona');
        });
        $this->addColumnIfNotExists('primer_apellido', function (Blueprint $table) {
            $table->string('primer_apellido')->nullable()->after('nombres');
        });
        $this->addColumnIfNotExists('segundo_apellido', function (Blueprint $table) {
            $table->string('segundo_apellido')->nullable()->after('primer_apellido');
        });
        $this->addColumnIfNotExists('email_confirmacion', function (Blueprint $table) {
            $table->string('email_confirmacion')->nullable()->after('email');
        });
        $this->addColumnIfNotExists('telefono_celular', function (Blueprint $table) {
            $table->string('telefono_celular')->nullable()->after('email_confirmacion');
        });
        $this->addColumnIfNotExists('telefono_fijo', function (Blueprint $table) {
            $table->string('telefono_fijo')->nullable()->after('telefono_celular');
        });
        $this->addColumnIfNotExists('nacionalidad', function (Blueprint $table) {
            $table->string('nacionalidad')->nullable()->after('telefono_fijo');
        });
        $this->addColumnIfNotExists('nacionalidad_especifica', function (Blueprint $table) {
            $table->string('nacionalidad_especifica')->nullable()->after('nacionalidad');
        });
        $this->addColumnIfNotExists('sexo', function (Blueprint $table) {
            $table->string('sexo')->nullable()->after('nacionalidad_especifica');
        });
        $this->addColumnIfNotExists('estado_civil', function (Blueprint $table) {
            $table->string('estado_civil')->nullable()->after('sexo');
        });
        $this->addColumnIfNotExists('tipo_identificacion', function (Blueprint $table) {
            $table->string('tipo_identificacion')->nullable()->after('estado_civil');
        });
        $this->addColumnIfNotExists('fecha_nacimiento', function (Blueprint $table) {
            $table->date('fecha_nacimiento')->nullable()->after('tipo_identificacion');
        });
        $this->addColumnIfNotExists('rfc', function (Blueprint $table) {
            $table->string('rfc')->nullable()->after('fecha_nacimiento');
        });
        $this->addColumnIfNotExists('curp', function (Blueprint $table) {
            $table->string('curp')->nullable()->after('rfc');
        });

        // Datos del Cónyuge (solo si estado_civil = 'casado')
        $this->addColumnIfNotExists('conyuge_nombres', function (Blueprint $table) {
            $table->string('conyuge_nombres')->nullable()->after('curp');
        });
        $this->addColumnIfNotExists('conyuge_primer_apellido', function (Blueprint $table) {
            $table->string('conyuge_primer_apellido')->nullable()->after('conyuge_nombres');
        });
        $this->addColumnIfNotExists('conyuge_segundo_apellido', function (Blueprint $table) {
            $table->string('conyuge_segundo_apellido')->nullable()->after('conyuge_primer_apellido');
        });
        $this->addColumnIfNotExists('conyuge_telefono', function (Blueprint $table) {
            $table->string('conyuge_telefono')->nullable()->after('conyuge_segundo_apellido');
        });

        // Campos Persona Moral
        $this->addColumnIfNotExists('razon_social', function (Blueprint $table) {
            $table->string('razon_social')->nullable()->after('tipo_persona');
        });
        $this->addColumnIfNotExists('dominio_internet', function (Blueprint $table) {
            $table->string('dominio_internet')->nullable()->after('razon_social');
        });
        $this->addColumnIfNotExists('telefono', function (Blueprint $table) {
            $table->string('telefono')->nullable()->after('dominio_internet');
        });
        $this->addColumnIfNotExists('calle', function (Blueprint $table) {
            $table->string('calle')->nullable()->after('telefono');
        });
        $this->addColumnIfNotExists('numero_exterior', function (Blueprint $table) {
            $table->string('numero_exterior')->nullable()->after('calle');
        });
        $this->addColumnIfNotExists('numero_interior', function (Blueprint $table) {
            $table->string('numero_interior')->nullable()->after('numero_exterior');
        });
        $this->addColumnIfNotExists('cp', function (Blueprint $table) {
            $table->string('cp')->nullable()->after('numero_interior');
        });
        $this->addColumnIfNotExists('colonia', function (Blueprint $table) {
            $table->string('colonia')->nullable()->after('cp');
        });
        $this->addColumnIfNotExists('municipio', function (Blueprint $table) {
            $table->string('municipio')->nullable()->after('colonia');
        });
        $this->addColumnIfNotExists('estado', function (Blueprint $table) {
            $table->string('estado')->nullable()->after('municipio');
        });
        $this->addColumnIfNotExists('ingreso_mensual_promedio', function (Blueprint $table) {
            $table->decimal('ingreso_mensual_promedio', 15, 2)->nullable()->after('estado');
        });
        $this->addColumnIfNotExists('referencias_ubicacion', function (Blueprint $table) {
            $table->text('referencias_ubicacion')->nullable()->after('ingreso_mensual_promedio');
        });

        // Datos del Acta Constitutiva
        $this->addColumnIfNotExists('notario_nombres', function (Blueprint $table) {
            $table->string('notario_nombres')->nullable()->after('referencias_ubicacion');
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

        // Apoderado Legal/Representante
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
        $this->addColumnIfNotExists('apoderado_telefono', function (Blueprint $table) {
            $table->string('apoderado_telefono')->nullable()->after('apoderado_sexo');
        });
        $this->addColumnIfNotExists('apoderado_extension', function (Blueprint $table) {
            $table->string('apoderado_extension')->nullable()->after('apoderado_telefono');
        });
        $this->addColumnIfNotExists('apoderado_email', function (Blueprint $table) {
            $table->string('apoderado_email')->nullable()->after('apoderado_extension');
        });
        $this->addColumnIfNotExists('facultades_en_acta', function (Blueprint $table) {
            $table->boolean('facultades_en_acta')->nullable()->after('apoderado_email');
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
        $this->addColumnIfNotExists('fecha_inscripcion_facultades', function (Blueprint $table) {
            $table->date('fecha_inscripcion_facultades')->nullable()->after('estado_registro_facultades');
        });
        $this->addColumnIfNotExists('tipo_representacion', function (Blueprint $table) {
            $table->string('tipo_representacion')->nullable()->after('fecha_inscripcion_facultades');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Verificar si user_id existe
        $hasUserId = Schema::hasColumn('tenants', 'user_id');

        if ($hasUserId) {
            // Eliminar la foreign key
            Schema::table('tenants', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });

            // Revertir user_id a not null (solo si existía antes)
            Schema::table('tenants', function (Blueprint $table) {
                $table->bigInteger('user_id')->unsigned()->nullable(false)->change();
            });

            // Volver a agregar la foreign key
            Schema::table('tenants', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        Schema::table('tenants', function (Blueprint $table) {

            // Eliminar todos los campos agregados
            $table->dropColumn([
                'tipo_persona',
                'nombres',
                'primer_apellido',
                'segundo_apellido',
                'email',
                'email_confirmacion',
                'telefono_celular',
                'telefono_fijo',
                'nacionalidad',
                'nacionalidad_especifica',
                'sexo',
                'estado_civil',
                'tipo_identificacion',
                'fecha_nacimiento',
                'rfc',
                'curp',
                'conyuge_nombres',
                'conyuge_primer_apellido',
                'conyuge_segundo_apellido',
                'conyuge_telefono',
                'razon_social',
                'dominio_internet',
                'telefono',
                'calle',
                'numero_exterior',
                'numero_interior',
                'cp',
                'colonia',
                'municipio',
                'estado',
                'ingreso_mensual_promedio',
                'referencias_ubicacion',
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
                'apoderado_telefono',
                'apoderado_extension',
                'apoderado_email',
                'facultades_en_acta',
                'escritura_publica_numero',
                'notario_numero_facultades',
                'fecha_escritura_facultades',
                'numero_inscripcion_registro_publico',
                'ciudad_registro_facultades',
                'estado_registro_facultades',
                'fecha_inscripcion_facultades',
                'tipo_representacion',
            ]);
        });
    }
};
