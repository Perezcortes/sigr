<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Buyer extends Model
{
    protected $guarded = [];

    protected $casts = [
        'historial_acciones' => 'array',
        'fecha_nacimiento' => 'date',
    ];

    public function asesor()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getNombreCompletoAttribute()
    {
        return trim("{$this->nombres} {$this->ap_paterno} {$this->ap_materno}");
    }
}