<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = [
    'rent_id',
    'user_id',
    'cuerpo',
    'visto',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rent(): BelongsTo
    {
        return $this->belongsTo(Rent::class);
    }
}
