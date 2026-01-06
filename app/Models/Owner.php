<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Owner extends Model
{
    protected $fillable = [
        'user_id',
        'asesor_id',
        'tipo_persona',
        // Persona Física - Información Personal
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
        // Domicilio Actual
        'calle',
        'numero_exterior',
        'numero_interior',
        'codigo_postal',
        'colonia',
        'delegacion_municipio',
        'estado',
        'referencias_ubicacion',
        // Forma de Pago
        'forma_pago',
        'forma_pago_otro',
        // Datos de Transferencia
        'titular_cuenta',
        'numero_cuenta',
        'nombre_banco',
        'clabe_interbancaria',
        // Representación
        'sera_representado',
        'tipo_representacion',
        // Datos del Representante
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
        // Persona Moral
        'razon_social',
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
        // Facultades en Acta
        'escritura_publica_numero',
        'notario_numero_facultades',
        'fecha_escritura_facultades',
        'numero_inscripcion_registro_publico',
        'ciudad_registro_facultades',
        'estado_registro_facultades',
        'tipo_representacion_moral',
    ];

    protected $casts = [
        'tipo_persona' => 'string',
        'estado_civil' => 'string',
        'regimen_conyugal' => 'string',
        'sexo' => 'string',
        'nacionalidad' => 'string',
        'tipo_identificacion' => 'string',
        'forma_pago' => 'string',
        'sera_representado' => 'string',
        'tipo_representacion' => 'string',
        'representante_sexo' => 'string',
        'fecha_constitucion' => 'date',
        'fecha_escritura_facultades' => 'date',
        'facultades_en_acta' => 'boolean',
        'apoderado_sexo' => 'string',
        'tipo_representacion_moral' => 'string',
    ];

    /**
     * Relación con User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ownerRequests(): HasMany
    {
        return $this->hasMany(OwnerRequest::class);
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
