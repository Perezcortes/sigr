<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Referral extends Model
{
    use HasFactory;

    /**
     * Permitimos asignación masiva para facilitar la ingesta desde Webhooks
     */
    protected $fillable = [
        'nombre',
        'correo',
        'telefono',
        'url_propiedad',
        'origen',        // Ej: 'App Movil', 'Web', 'Campaña FB'
        'responsable_id', // Asesor asignado (opcional)
        'status',        // 'nuevo', 'contactado', etc.
        'payload_original' // JSON crudo recibido del webhook
    ];

    /**
     * Conversión automática de tipos
     */
    protected $casts = [
        'payload_original' => 'array', // Para poder leer el JSON como array en PHP
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación: Un referido puede tener un asesor responsable
     */
    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }
}