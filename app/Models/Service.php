<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Service extends Model
{
    protected $fillable = [
        'rent_id',
        'tipo',
        'mes_correspondiente',
        'fecha_pago',
        'monto',
        'forma_pago',
        'evidencia',
        'observaciones',
        'estatus',
    ];

    protected $casts = [
        'fecha_pago' => 'date',
        'monto' => 'decimal:2',
    ];

    public function rent(): BelongsTo
    {
        return $this->belongsTo(Rent::class);
    }
}