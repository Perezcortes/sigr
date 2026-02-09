<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentSetting extends Model
{
    protected $fillable = [
        'rent_id', 'tipo', 'frecuencia', 'monto', 
        'moneda', 'es_variable', 'recordatorio', 'activo'
    ];

    public function rent(): BelongsTo
    {
        return $this->belongsTo(Rent::class);
    }
}