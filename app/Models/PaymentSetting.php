<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentSetting extends Model
{
    public const FREQUENCY_INTERVALS = [
        'Mensual' => 1,
        'Bimestral' => 2,
        'Trimestral' => 3,
        'Semestral' => 6,
        'Anual' => 12,
    ];

    protected $fillable = [
        'rent_id',
        'tipo',
        'frecuencia',
        'dia_pago',
        'meses_intervalo',
        'fecha_limite_pago',
        'monto',
        'moneda',
        'es_variable',
        'recordatorio',
        'activo',
        'es_base_renta',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha_limite_pago' => 'date',
        'es_variable' => 'boolean',
        'activo' => 'boolean',
        'es_base_renta' => 'boolean',
    ];

    public function rent(): BelongsTo
    {
        return $this->belongsTo(Rent::class);
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(PaymentReminder::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public static function intervalForFrequency(string $frequency): int
    {
        return self::FREQUENCY_INTERVALS[$frequency] ?? 1;
    }
}
