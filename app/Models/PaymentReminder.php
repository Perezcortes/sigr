<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentReminder extends Model
{
    protected $fillable = [
        'payment_setting_id',
        'dias_antes',
        'direccion',
        'activo',
    ];

    protected $casts = [
        'dias_antes' => 'integer',
        'activo' => 'boolean',
    ];

    public function paymentSetting(): BelongsTo
    {
        return $this->belongsTo(PaymentSetting::class);
    }
}
