<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyDocument extends Model
{
    protected $table = 'property_documents';

    protected $fillable = [
        'property_id',
        'rent_id',
        'user_id',
        'mime',
        'path_file',
        'tag',
        'user_name',
    ];

    protected static function boot(): void
    {
        parent::boot();

        // Auto-pobla property_id desde la renta para que ViewRent no necesite cambios
        static::creating(function ($doc) {
            if (empty($doc->property_id) && $doc->rent_id) {
                $doc->property_id = \App\Models\Rent::find($doc->rent_id)?->property_id;
            }
        });
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function rent(): BelongsTo
    {
        return $this->belongsTo(Rent::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function tipos(): array
    {
        return [
            'escrituras' => 'Escrituras',
            'predial' => 'Predial',
            'recibo_agua' => 'Recibo de Agua',
            'recibo_luz' => 'Recibo de Luz',
            'recibo_gas' => 'Recibo de Gas',
            'fotos_propiedad' => 'Fotos de la Propiedad',
            'contrato_anterior' => 'Contrato Anterior',
            'otro' => 'Otro',
        ];
    }
}

