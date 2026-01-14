<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    protected $table = 'leads'; 

    protected $fillable = [
        'nombre',
        'correo',
        'telefono',
        'origen',
        'mensaje',
        'url_propiedad',
        'etapa',
        'responsable_id',
        'payload_original',
        'tipo_transaccion',
    ];

    protected $casts = [
        'payload_original' => 'array',
    ];

    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }
}