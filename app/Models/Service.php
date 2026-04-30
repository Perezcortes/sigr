<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Service extends Model
{
    protected $fillable = [
        'rent_id',
        'payment_setting_id',
        'nombre',
        'tipo',
        'frecuencia',
        'mes_correspondiente',
        'periodo_referencia',
        'fecha_vencimiento',
        'fecha_pago',
        'monto',
        'forma_pago',
        'evidencia',
        'observaciones',
        'estatus',
    ];

    protected $casts = [
        'fecha_pago' => 'date',
        'fecha_vencimiento' => 'date',
        'monto' => 'decimal:2',
    ];

    public function rent(): BelongsTo
    {
        return $this->belongsTo(Rent::class);
    }

    public function paymentSetting(): BelongsTo
    {
        return $this->belongsTo(PaymentSetting::class);
    }
}
