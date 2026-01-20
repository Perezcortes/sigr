<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $fillable = [
    'rent_id',
    'user_id',
    'tipo', // peticion, incidencia
    'titulo',
    'descripcion',
    'estatus', // nueva, en_proceso, completada
    'comentarios_admin',
    ];

    public function rent() { return $this->belongsTo(Rent::class); }
    public function user() { return $this->belongsTo(User::class); }
}
