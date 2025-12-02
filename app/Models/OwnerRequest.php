<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OwnerRequest extends Model
{
    protected $fillable = [
        'owner_id',
        'rent_id',
        'estatus',
        'tipo_persona',

        // Campos para Persona Moral
        'razon_social',
        'dominio_internet',

        // Campos para Acta Constitutiva (Persona Moral)
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
        
        // Campos para Apoderado Legal (Persona Moral)
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
        
        // Campos para Facultades en Acta (Persona Moral)
        'escritura_publica_numero',
        'notario_numero_facultades',
        'fecha_escritura_facultades',
        'numero_inscripcion_registro_publico',
        'ciudad_registro_facultades',
        'estado_registro_facultades',
        'tipo_representacion_moral',
        'tipo_representacion_otro',

        // Datos del Propietario
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
        // Domicilio
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
        'titular_cuenta',
        'numero_cuenta',
        'nombre_banco',
        'clabe_interbancaria',
        // Datos del Inmueble
        'tipo_inmueble',
        'uso_suelo',
        'mascotas',
        'mascotas_especifica',
        'precio_renta',
        'iva_renta',
        'frecuencia_pago',
        'frecuencia_pago_otra',
        'condiciones_pago',
        'deposito_garantia',
        'paga_mantenimiento',
        'quien_paga_mantenimiento',
        'mantenimiento_incluido_renta',
        'costo_mantenimiento_mensual',
        'instrucciones_pago',
        'requiere_seguro',
        'cobertura_seguro',
        'monto_cobertura_seguro',
        'servicios_pagar',
        // Dirección del Inmueble
        'inmueble_calle',
        'inmueble_numero_exterior',
        'inmueble_numero_interior',
        'inmueble_codigo_postal',
        'inmueble_colonia',
        'inmueble_delegacion_municipio',
        'inmueble_estado',
        'inmueble_referencias',
        'inmueble_inventario',
        // Representación
        'sera_representado',
        'tipo_representacion',
        // Representante
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
        'representante_codigo_postal',
        'representante_colonia',
        'representante_delegacion_municipio',
        'representante_estado',
        'representante_referencias',
    ];

    protected $casts = [
        'precio_renta' => 'decimal:2',
        'deposito_garantia' => 'decimal:2',
        'costo_mantenimiento_mensual' => 'decimal:2',
        'monto_cobertura_seguro' => 'decimal:2',
        'fecha_constitucion' => 'date',
        'fecha_escritura_facultades' => 'date',
        'facultades_en_acta' => 'boolean',
    ];

    /**
     * Relación con Owner
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    /**
     * Relación con Rent
     */
    public function rent(): BelongsTo
    {
        return $this->belongsTo(Rent::class);
    }

    /**
     * Obtiene el nombre completo del propietario
     */
    public function getNombreCompletoAttribute(): string
    {
        return trim($this->nombres . ' ' . $this->primer_apellido . ' ' . ($this->segundo_apellido ?? ''));
    }

    public function syncWithOwner()
    {
        if ($this->owner) {
            $this->owner->update([
                'nombres' => $this->nombres,
                'primer_apellido' => $this->primer_apellido,
                'segundo_apellido' => $this->segundo_apellido,
                'email' => $this->email,
                'telefono' => $this->telefono,
                'estado_civil' => $this->estado_civil,
                'regimen_conyugal' => $this->regimen_conyugal,
                'sexo' => $this->sexo,
                'nacionalidad' => $this->nacionalidad,
                'tipo_identificacion' => $this->tipo_identificacion,
                'rfc' => $this->rfc,
                'curp' => $this->curp,
                'calle' => $this->calle,
                'numero_exterior' => $this->numero_exterior,
                'numero_interior' => $this->numero_interior,
                'codigo_postal' => $this->codigo_postal,
                'colonia' => $this->colonia,
                'delegacion_municipio' => $this->delegacion_municipio,
                'estado' => $this->estado,
                'referencias_ubicacion' => $this->referencias_ubicacion,
                'forma_pago' => $this->forma_pago,
                'forma_pago_otro' => $this->forma_pago_otro,
                'titular_cuenta' => $this->titular_cuenta,
                'numero_cuenta' => $this->numero_cuenta,
                'nombre_banco' => $this->nombre_banco,
                'clabe_interbancaria' => $this->clabe_interbancaria,
                'sera_representado' => $this->sera_representado,
                'tipo_representacion' => $this->tipo_representacion,
                // Campos para Persona Moral
                'razon_social' => $this->razon_social,
                'dominio_internet' => $this->dominio_internet,
                'notario_nombres' => $this->notario_nombres,
                'notario_primer_apellido' => $this->notario_primer_apellido,
                'notario_segundo_apellido' => $this->notario_segundo_apellido,
                'numero_escritura' => $this->numero_escritura,
                'fecha_constitucion' => $this->fecha_constitucion,
                'notario_numero' => $this->notario_numero,
                'ciudad_registro' => $this->ciudad_registro,
                'estado_registro' => $this->estado_registro,
                'numero_registro_inscripcion' => $this->numero_registro_inscripcion,
                'giro_comercial' => $this->giro_comercial,
                'apoderado_nombres' => $this->apoderado_nombres,
                'apoderado_primer_apellido' => $this->apoderado_primer_apellido,
                'apoderado_segundo_apellido' => $this->apoderado_segundo_apellido,
                'apoderado_sexo' => $this->apoderado_sexo,
                'apoderado_curp' => $this->apoderado_curp,
                'apoderado_email' => $this->apoderado_email,
                'apoderado_telefono' => $this->apoderado_telefono,
                'apoderado_calle' => $this->apoderado_calle,
                'apoderado_numero_exterior' => $this->apoderado_numero_exterior,
                'apoderado_numero_interior' => $this->apoderado_numero_interior,
                'apoderado_cp' => $this->apoderado_cp,
                'apoderado_colonia' => $this->apoderado_colonia,
                'apoderado_municipio' => $this->apoderado_municipio,
                'apoderado_estado' => $this->apoderado_estado,
                'facultades_en_acta' => $this->facultades_en_acta,
                'escritura_publica_numero' => $this->escritura_publica_numero,
                'notario_numero_facultades' => $this->notario_numero_facultades,
                'fecha_escritura_facultades' => $this->fecha_escritura_facultades,
                'numero_inscripcion_registro_publico' => $this->numero_inscripcion_registro_publico,
                'ciudad_registro_facultades' => $this->ciudad_registro_facultades,
                'estado_registro_facultades' => $this->estado_registro_facultades,
                'tipo_representacion_moral' => $this->tipo_representacion_moral,
            ]);
        }
    }
}