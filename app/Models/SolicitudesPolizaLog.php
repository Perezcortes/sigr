<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SolicitudesPolizaLog extends Model
{
    protected $guarded = [];

    public function rent()
    {
        return $this->belongsTo(Rent::class);
    }
}