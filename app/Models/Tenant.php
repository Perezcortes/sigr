<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tenant extends Model
{
    protected $fillable = [
        'user_id',
        'tipo_persona',
        // Persona Física
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
        // Datos del Cónyuge
        'conyuge_nombres',
        'conyuge_primer_apellido',
        'conyuge_segundo_apellido',
        'conyuge_telefono',
        // Persona Moral
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
        // Acta Constitutiva
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
        // Apoderado Legal
        'apoderado_nombres',
        'apoderado_primer_apellido',
        'apoderado_segundo_apellido',
        'apoderado_sexo',
        'apoderado_telefono',
        'apoderado_extension',
        'apoderado_email',
        'facultades_en_acta',
        // Facultades en Acta
        'escritura_publica_numero',
        'notario_numero_facultades',
        'fecha_escritura_facultades',
        'numero_inscripcion_registro_publico',
        'ciudad_registro_facultades',
        'estado_registro_facultades',
        'fecha_inscripcion_facultades',
        'tipo_representacion',
    ];

    protected $casts = [
        'tipo_persona' => 'string',
        'nacionalidad' => 'string',
        'sexo' => 'string',
        'estado_civil' => 'string',
        'tipo_identificacion' => 'string',
        'fecha_nacimiento' => 'date',
        'fecha_constitucion' => 'date',
        'fecha_escritura_facultades' => 'date',
        'fecha_inscripcion_facultades' => 'date',
        'facultades_en_acta' => 'boolean',
        'ingreso_mensual_promedio' => 'decimal:2',
        'apoderado_sexo' => 'string',
        'tipo_representacion' => 'string',
    ];

    /**
     * Relación con User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Verifica si es persona física
     */
    public function isPersonaFisica(): bool
    {
        return $this->tipo_persona === 'fisica';
    }

    /**
     * Verifica si es persona moral
     */
    public function isPersonaMoral(): bool
    {
        return $this->tipo_persona === 'moral';
    }

    /**
     * Obtiene el nombre completo según el tipo de persona
     */
    public function getNombreCompletoAttribute(): string
    {
        if ($this->isPersonaFisica()) {
            $nombre = trim(
                ($this->nombres ?? '') . ' ' .
                ($this->primer_apellido ?? '') . ' ' .
                ($this->segundo_apellido ?? '')
            );
            return $nombre ?: 'Sin nombre';
        }

        if ($this->isPersonaMoral()) {
            return $this->razon_social ?? 'Sin razón social';
        }

        return 'Sin tipo de persona';
    }
}
