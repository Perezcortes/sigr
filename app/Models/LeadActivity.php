<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadActivity extends Model
{
    protected $table = 'lead_activities';

    protected $fillable = [
        'lead_id',
        'user_id',
        'fecha',
        'hora',
        'descripcion',
        'completada',
    ];

    protected $casts = [
        'fecha'      => 'date',
        'completada' => 'boolean',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Fecha y hora combinadas como string legible (ej. "Lun 16 May · 10:00")
     */
    public function getFechaHoraAttribute(): string
    {
        $fecha = $this->fecha ? $this->fecha->translatedFormat('D d M') : '—';
        $hora  = $this->hora ?? '';

        return $hora ? "{$fecha} · {$hora}" : $fecha;
    }
}
