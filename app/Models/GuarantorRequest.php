<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuarantorRequest extends Model
{
    use HasFactory;

    // Aceptamos todos los campos del formulario dinámicamente
    protected $guarded = [];

    // Relación: Esta solicitud le pertenece a una renta específica
    public function rent(): BelongsTo
    {
        return $this->belongsTo(Rent::class);
    }
}