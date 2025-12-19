<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Application extends Model
{
    protected $fillable = [
        'user_id',
        'folio',
        'estatus',
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
        // Campos de Uso de Propiedad
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
    ];

    protected $casts = [
        'fecha_ingreso' => 'date',
        'ingreso_mensual_comprobable' => 'decimal:2',
        'ingreso_mensual_no_comprobable' => 'decimal:2',
        'otra_persona_aporta' => 'boolean',
        'persona_aporta_ingreso_comprobable' => 'decimal:2',
        'sustituye_otro_domicilio' => 'boolean',
    ];

    /**
     * Boot del modelo para generar el folio automáticamente
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($application) {
            if (empty($application->folio)) {
                $application->folio = self::generateFolio();
            }
        });
    }

    /**
     * Genera un folio único con el formato: APP-2025-0001
     */
    public static function generateFolio(): string
    {
        $year = now()->year;
        $prefix = "APP-{$year}-";

        // Obtener el último folio del año actual
        $lastApplication = self::where('folio', 'like', "{$prefix}%")
            ->orderBy('folio', 'desc')
            ->first();

        if ($lastApplication) {
            // Extraer el número del último folio
            $lastNumber = (int) substr($lastApplication->folio, -4);
            $newNumber = $lastNumber + 1;
        } else {
            // Si no hay folios del año actual, empezar en 1
            $newNumber = 1;
        }

        // Formatear el número con 4 dígitos (0001, 0002, etc.)
        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Relación con User (tenant)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con Rents
     */
    public function rents(): HasMany
    {
        return $this->hasMany(Rent::class);
    }
}
