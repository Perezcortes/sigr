<?php

namespace App\Models;

use App\Models\Traits\HasHashId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rent extends Model
{
    use HasHashId;
    // ⚠️ Asegúrate de que estos campos existan en tu tabla 'rents'
    protected $fillable = [
        'office_id',
        'tenant_id',
        // Asegúrate de que 'owner_id' esté aquí si vas a usarlo en el formulario
        'owner_id', 
        'start_date',
        'end_date',
        'amount',
        'payment_frequency',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'amount' => 'decimal:2',
    ];
    
    // --- Relaciones ---

    /**
     * Relación con la Oficina/Propiedad
     */
    public function office(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Office::class);
    }

    /**
     * Relación con el Inquilino
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    /**
     * Relación con el Propietario (¡SOLUCIÓN AL ERROR!)
     */
    public function owner(): BelongsTo // <--- ESTA RELACIÓN DEBE EXISTIR
    {
        return $this->belongsTo(\App\Models\Owner::class);
    }
}