<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Estate extends Model
{
    protected $fillable = [
        'nombre',
    ];

    public function offices(): HasMany
    {
        return $this->hasMany(Office::class);
    }

    public function municipalities(): HasMany
    {
        return $this->hasMany(Municipality::class, 'state_id');
    }
}