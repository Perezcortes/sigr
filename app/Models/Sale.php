<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Buyer;
use App\Models\Seller;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $nombres
 * @property string $ap_paterno
 * @property string|null $ap_materno
 */

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

        // --- DISPARADOR DE COBRO (CENTRO DE PAGOS) ---
        static::updated(function (Sale $sale) {
            $debeCobrarse = false;
            
            if ($sale->isDirty('estatus_operacion')) {
                if ($sale->momento_cobro_comision === 'a_la_venta' && $sale->estatus_operacion === 'Contrato firmado') {
                    $debeCobrarse = true;
                } 
                elseif ($sale->momento_cobro_comision === 'a_la_escrituracion' && $sale->estatus_operacion === 'Cerrada') {
                    $debeCobrarse = true;
                }
            }

            if ($debeCobrarse) {
                \App\Models\PayableOperation::firstOrCreate(
                    [
                        'payable_type' => Sale::class,
                        'payable_id' => $sale->id,
                    ],
                    [
                        'user_id' => $sale->user_id, 
                        'nombre_cliente' => $sale->nombre_cliente_principal,
                        'fecha_firma' => now(), 
                        'monto_operacion' => $sale->monto_operacion ?? 0,
                        'monto_comision' => $sale->comision_monto ?? 0,
                        'regalia' => ($sale->comision_monto ?? 0) * 0.12, // 12% de la comisión
                        'estatus' => 'pendiente de pago',
                        'fecha_vencimiento' => now()->addDays(10), 
                    ]
                );
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
    
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Buyer::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }
}