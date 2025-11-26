<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyDocument extends Model
{
    protected $table = 'property_documents';

    protected $fillable = [
        'rent_id',
        'user_id',
        'mime',
        'path_file',
        'tag',
        'user_name',
    ];

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

