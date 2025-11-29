<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RentComment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'rent_id',
        'user_id',
        'comment',
        'status',
    ];

    public function rent(): BelongsTo
    {
        return $this->belongsTo(Rent::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

