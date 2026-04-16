<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Municipality extends Model
{
    protected $fillable = [
        'name',
        'state_id',
    ];

    public function estate(): BelongsTo
    {
        return $this->belongsTo(Estate::class, 'state_id');
    }
}
