<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    protected $fillable = [
        'nombre',
        'estate_id', 
    ];

    // Relación con Oficinas
    public function offices(): HasMany
    {
        return $this->hasMany(Office::class);
    }

    // Relación con Estado 
    public function estate(): BelongsTo
    {
        return $this->belongsTo(Estate::class);
    }
}