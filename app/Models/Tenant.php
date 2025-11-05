<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $fillable = [
        'tipo_persona',
        'nombre',
        'primer_apellido',
        'segundo_apellido',
    ];
}
