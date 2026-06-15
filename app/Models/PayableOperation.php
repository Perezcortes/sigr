<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PayableOperation extends Model
{
    protected $fillable = [
        'payable_type',
        'payable_id',
        'user_id',
        'nombre_cliente',
        'fecha_firma',
        'monto_operacion',
        'monto_comision',
        'regalia',
        'estatus',
        'fecha_vencimiento',
        'fecha_pago',
    ];

    protected $casts = [
        'fecha_firma' => 'date',
        'fecha_vencimiento' => 'datetime',
        'fecha_pago' => 'datetime',
        'monto_operacion' => 'decimal:2',
        'monto_comision' => 'decimal:2',
        'regalia' => 'decimal:2',
    ];

    // Relación polimórfica: nos dirá si es una Renta o una Venta
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    // El agente que debe pagar
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}