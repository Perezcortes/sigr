<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantRequest extends Model
{
    protected $fillable = [
    'tenant_id',
    'rent_id',
    'estatus',
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
    'calle',
    'numero_exterior',
    'numero_interior',
    'codigo_postal',
    'colonia',
    'delegacion_municipio',
    'estado',
    'referencias_ubicacion',
    'situacion_habitacional',
    'arrendador_actual_nombres',
    'arrendador_actual_primer_apellido',
    'arrendador_actual_segundo_apellido',
    'arrendador_actual_telefono',
    'renta_actual',
    'ocupa_desde_ano',
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
    'numero_adultos',
    'nombre_adulto_1',
    'nombre_adulto_2',
    'nombre_adulto_3',
    'nombre_adulto_4',
    'tiene_menores',
    'cuantos_menores',
    'tiene_mascotas',
    'especificar_mascotas',
    'referencia_personal1_nombres',
    'referencia_personal1_primer_apellido',
    'referencia_personal1_segundo_apellido',
    'referencia_personal1_relacion',
    'referencia_personal1_telefono',
    'referencia_personal2_nombres',
    'referencia_personal2_primer_apellido',
    'referencia_personal2_segundo_apellido',
    'referencia_personal2_relacion',
    'referencia_personal2_telefono',
    'referencia_familiar1_nombres',
    'referencia_familiar1_primer_apellido',
    'referencia_familiar1_segundo_apellido',
    'referencia_familiar1_relacion',
    'referencia_familiar1_telefono',
    'referencia_familiar2_nombres',
    'referencia_familiar2_primer_apellido',
    'referencia_familiar2_segundo_apellido',
    'referencia_familiar2_relacion',
    'referencia_familiar2_telefono',
    'razon_social',
    'dominio_internet',
    'ingreso_mensual_promedio',
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
    'tipo_representacion_otro',
    'referencia_comercial1_empresa',
    'referencia_comercial1_contacto',
    'referencia_comercial1_telefono',
    'referencia_comercial2_empresa',
    'referencia_comercial2_contacto',
    'referencia_comercial2_telefono',
    'referencia_comercial3_empresa',
    'referencia_comercial3_contacto',
    'referencia_comercial3_telefono',
    ];

    protected $casts = [
        'precio_renta' => 'decimal:2',
        'deposito_garantia' => 'decimal:2',
        'costo_mantenimiento_mensual' => 'decimal:2',
        'monto_cobertura_seguro' => 'decimal:2',
    ];

    /**
     * Relación con Tenant
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Relación con Rent
     */
    public function rent(): BelongsTo
    {
        return $this->belongsTo(Rent::class);
    }

    /**
     * Obtiene el nombre completo del inquilino
     */
    public function getNombreCompletoAttribute(): string
    {
        return trim($this->nombres . ' ' . $this->primer_apellido . ' ' . ($this->segundo_apellido ?? ''));
    }

    public function syncWithTenant()
    {
        if ($this->tenant) {
            $this->tenant->update([
                'nombres' => $this->nombres,
                'primer_apellido' => $this->primer_apellido,
                'segundo_apellido' => $this->segundo_apellido,
                'email' => $this->email,
                'telefono_celular' => $this->telefono_celular,
                'telefono_fijo' => $this->telefono_fijo,
                'estado_civil' => $this->estado_civil,
                'sexo' => $this->sexo,
                'nacionalidad' => $this->nacionalidad,
                'nacionalidad_especifica' => $this->nacionalidad_especifica,
                'tipo_identificacion' => $this->tipo_identificacion,
                'rfc' => $this->rfc,
                'curp' => $this->curp,
            ]);
        }
    }
}