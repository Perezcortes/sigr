<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $fillable = [
    'rent_id',
    'user_id',
    'titulo',
    'descripcion',
    'estatus', 
    'evidencia',
    ];

    public function rent() { return $this->belongsTo(Rent::class); }
    public function user() { return $this->belongsTo(User::class); }
}
