<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo local que refleja el esquema real de whatsapp_messages.
 *
 * Columnas: id, user_id, lead_id, direction enum('in','out'),
 *           phone, body, wa_message_id, sent_at, created_at, updated_at
 */
class WhatsappMessage extends Model
{
    protected $table = 'whatsapp_messages';

    protected $fillable = [
        'user_id',
        'lead_id',
        'direction',
        'phone',
        'body',
        'wa_message_id',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isIncoming(): bool
    {
        return $this->direction === 'in';
    }

    public function isOutgoing(): bool
    {
        return $this->direction === 'out';
    }
}
