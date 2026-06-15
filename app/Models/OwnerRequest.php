<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OwnerRequest extends Model
{
    protected $guarded = [];

    protected $casts = [
        'precio_renta' => 'decimal:2',
        'deposito_garantia' => 'decimal:2',
        'costo_mantenimiento_mensual' => 'decimal:2',
        'monto_cobertura_seguro' => 'decimal:2',
        'fecha_constitucion' => 'date',
        'fecha_escritura_facultades' => 'date',
        'fecha_inscripcion_facultades' => 'date',
        'facultades_en_acta' => 'string',
        'fecha_nacimiento' => 'date',
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

                'nacionalidad_especifica' => $this->nacionalidad_especifica,
                'pais_origen' => $this->pais_origen,
                'fecha_vencimiento_tarjeta' => $this->fecha_vencimiento_tarjeta,
                'nue' => $this->nue,
                'tipo_residencia' => $this->tipo_residencia,
                'mismo_domicilio_fiscal' => $this->mismo_domicilio_fiscal,
                'calle_fiscal' => $this->calle_fiscal,
                'numero_exterior_fiscal' => $this->numero_exterior_fiscal,
                'numero_interior_fiscal' => $this->numero_interior_fiscal,
                'codigo_postal_fiscal' => $this->codigo_postal_fiscal,
                'colonia_fiscal' => $this->colonia_fiscal,
                'municipio_fiscal' => $this->municipio_fiscal,
                'estado_fiscal' => $this->estado_fiscal,
                'metros_cuadrados' => $this->metros_cuadrados,
                'fecha_nacimiento' => $this->fecha_nacimiento,
                'regimen_fiscal' => $this->regimen_fiscal,
            ]);
        }
    }
}