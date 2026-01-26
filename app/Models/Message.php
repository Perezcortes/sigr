<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
    'rent_id',
    'user_id',
    'cuerpo',
    'visto',
    ];

    public function rent() { return $this->belongsTo(Rent::class); }
    public function user() { return $this->belongsTo(User::class); }
}
