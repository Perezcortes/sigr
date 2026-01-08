<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sale extends Model
{
    use HasFactory;

    /**
     * Deshabilitamos $fillable y usamos $guarded = [] 
     * porque son más de 50 campos y es más fácil mantenerlo así.
     */
    protected $guarded = [];

    /**
     * Conversión de tipos CRÍTICA para que funcionen los Repeaters de Filament
     */
    protected $casts = [
        // Campos JSON (Repeaters)
        'compradores_adicionales' => 'array',
        'comprador_actividades_adicionales' => 'array',
        'vendedores_adicionales' => 'array',
        'bitacora_operacion' => 'array',
        'hipoteca_bancos_adicionales' => 'array',

        // Booleanos y Fechas
        'requiere_hipoteca' => 'boolean',
        'fecha_inicio' => 'date',
        'fecha_probable_cierre' => 'date',
        'comprador_fecha_nacimiento' => 'date',
        'vendedor_fecha_nacimiento' => 'date',
        
        // Decimales (Opcional, para asegurar precisión matemática)
        'monto_operacion' => 'decimal:2',
        'comision_monto' => 'decimal:2',
        'hipoteca_monto_aprobado' => 'decimal:2',
    ];

    /**
     * Lógica automática al guardar
     */
    protected static function booted(): void
    {
        static::saving(function (Sale $sale) {
            // Concatenar automáticamente el nombre del cliente principal para mostrar en la tabla
            // Si hay nombre, úsalo, si no, pon 'Sin Nombre'
            $nombre = $sale->comprador_nombres ?? '';
            $apellido = $sale->comprador_ap_paterno ?? '';
            
            $sale->nombre_cliente_principal = trim("$nombre $apellido") ?: 'Nuevo Cliente';
        });
        
        static::creating(function (Sale $sale) {
            // Asignar fecha de inicio hoy si no viene definida
            if (empty($sale->fecha_inicio)) {
                $sale->fecha_inicio = now();
            }
            // Asignar el usuario creador si no viene definido
            if (empty($sale->user_id) && auth()->check()) {
                $sale->user_id = auth()->id();
            }
        });
    }

    /**
     * Relación: Usuario que creó el proceso de venta (Asesor)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}