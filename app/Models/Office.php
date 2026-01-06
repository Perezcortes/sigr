<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Office extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'nombre',
        'telefono',
        'correo',
        'responsable',
        'clave',
        'estatus_actividad',
        'estatus_recibir_leads',
        'calle',
        'numero_interior',
        'numero_exterior',
        'colonia',
        'delegacion_municipio',
        'codigo_postal',
        'ciudad',
        'estate_id',
        'lat',
        'lng',
    ];

    protected $casts = [
        'estatus_actividad' => 'boolean',
        'estatus_recibir_leads' => 'boolean',
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function estate(): BelongsTo
    {
        return $this->belongsTo(Estate::class);
    }
}
