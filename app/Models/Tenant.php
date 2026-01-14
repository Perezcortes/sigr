<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    protected $fillable = [
        'user_id',
        'asesor_id',
        'tipo_persona',
        // Campos para insertar desde interesados
        'nombre',
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
        // Campos de Empleo
        'profesion_oficio_puesto',
        'tipo_empleo',
        'telefono_empleo',
        'extension_empleo',
        'empresa_trabaja',
        'calle_empleo',
        'numero_exterior_empleo',
        'numero_interior_empleo',
        'codigo_postal_empleo',
        'colonia_empleo',
        'delegacion_municipio_empleo',
        'estado_empleo',
        'fecha_ingreso',
        'jefe_nombres',
        'jefe_primer_apellido',
        'jefe_segundo_apellido',
        'jefe_telefono',
        'jefe_extension',
        // Campos de Ingresos
        'ingreso_mensual_comprobable',
        'ingreso_mensual_no_comprobable',
        'numero_personas_dependen',
        'otra_persona_aporta',
        'numero_personas_aportan',
        'persona_aporta_nombres',
        'persona_aporta_primer_apellido',
        'persona_aporta_segundo_apellido',
        'persona_aporta_parentesco',
        'persona_aporta_telefono',
        'persona_aporta_empresa',
        'persona_aporta_ingreso_comprobable',
        // Campos de Uso de Propiedad inmueble comercial
        'tipo_inmueble_desea',
        'giro_negocio',
        'experiencia_giro',
        'propositos_arrendamiento',
        'sustituye_otro_domicilio',
        'domicilio_anterior_calle',
        'domicilio_anterior_numero_exterior',
        'domicilio_anterior_numero_interior',
        'domicilio_anterior_codigo_postal',
        'domicilio_anterior_colonia',
        'domicilio_anterior_delegacion_municipio',
        'domicilio_anterior_estado',
        'motivo_cambio_domicilio',
        // Campos para uso Residencial
        'numero_adultos',
        'nombre_adulto_1',
        'nombre_adulto_2',
        'nombre_adulto_3',
        'nombre_adulto_4',
        'tiene_menores',
        'cuantos_menores',
        'tiene_mascotas',
        'especificar_mascotas',
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
        // Campos de Empleo
        'fecha_ingreso' => 'date',
        // Campos de Ingresos
        'ingreso_mensual_comprobable' => 'decimal:2',
        'ingreso_mensual_no_comprobable' => 'decimal:2',
        'otra_persona_aporta' => 'boolean',
        'persona_aporta_ingreso_comprobable' => 'decimal:2',
        // Campos de Uso de Propiedad
        'sustituye_otro_domicilio' => 'boolean',
    ];

    /**
     * Relación con User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenantRequests(): HasMany
    {
        return $this->hasMany(TenantRequest::class);
    }

    /**
     * Relación con Applications
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'user_id', 'user_id');
    }

    /**
     * Relación con el Asesor que creó el Tenant
     */
    public function asesor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'asesor_id');
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
