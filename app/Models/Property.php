<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Property extends Model
{
    protected $fillable = [
        'user_id',
        'folio',
        'estatus',
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
        'calle',
        'numero_exterior',
        'numero_interior',
        'codigo_postal',
        'colonia',
        'delegacion_municipio',
        'estado',
        'referencias_ubicacion',
        'inventario',
        // Columnas antiguas (para compatibilidad)
        'tipo',
        'direccion',
        'nombre',
        'activo',
        'city_id',
        'estate_id',
    ];

    protected $casts = [
        'precio_renta' => 'decimal:2',
        'deposito_garantia' => 'decimal:2',
        'costo_mantenimiento_mensual' => 'decimal:2',
        'monto_cobertura_seguro' => 'decimal:2',
    ];

    /**
     * Boot del modelo para generar el folio automáticamente
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($property) {
            if (empty($property->folio)) {
                $property->folio = self::generateFolio();
            }
            
            // Proporcionar valores por defecto para columnas antiguas que son NOT NULL
            if (empty($property->tipo)) {
                $property->tipo = $property->tipo_inmueble ?? 'Casa';
            }
            if (empty($property->direccion)) {
                $property->direccion = ($property->calle ?? '') . ' ' . ($property->numero_exterior ?? '');
            }
            if (empty($property->nombre)) {
                $property->nombre = $property->tipo_inmueble ?? 'Propiedad';
            }
            if (empty($property->activo)) {
                $property->activo = $property->estatus === 'disponible' ? 1 : 0;
            }
            // city_id y estate_id deben ser NULL si no tienen valor (no 0, porque tienen foreign keys)
            if (empty($property->city_id)) {
                $property->city_id = null;
            }
            if (empty($property->estate_id)) {
                $property->estate_id = null;
            }
        });
    }

    /**
     * Genera un folio único con el formato: PROP-2025-0001
     */
    public static function generateFolio(): string
    {
        $year = now()->year;
        $prefix = "PROP-{$year}-";

        // Obtener el último folio del año actual
        $lastProperty = self::where('folio', 'like', "{$prefix}%")
            ->orderBy('folio', 'desc')
            ->first();

        if ($lastProperty) {
            // Extraer el número del último folio
            $lastNumber = (int) substr($lastProperty->folio, -4);
            $newNumber = $lastNumber + 1;
        } else {
            // Si no hay folios del año actual, empezar en 1
            $newNumber = 1;
        }

        // Formatear el número con 4 dígitos (0001, 0002, etc.)
        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Relación con User (owner)
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
