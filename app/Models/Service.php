<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
    'rent_id',
    'nombre',
    'monto',
    'frecuencia',
    ];

    public function rent() { return $this->belongsTo(Rent::class); }
}
