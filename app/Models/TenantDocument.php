<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantDocument extends Model
{
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

    /**
     * Tipos de documentos para persona física
     */
    public static function tiposPersonaFisica(): array
    {
        return [
            'ine_frente' => 'INE (Frente)',
            'ine_reverso' => 'INE (Reverso)',
            'comprobante_domicilio' => 'Comprobante de Domicilio',
            'comprobante_ingresos' => 'Comprobante de Ingresos',
            'referencias_personales' => 'Referencias Personales',
            'referencias_laborales' => 'Referencias Laborales',
            'otro' => 'Otro',
        ];
    }

    /**
     * Tipos de documentos para persona moral
     */
    public static function tiposPersonaMoral(): array
    {
        return [
            'acta_constitutiva' => 'Acta Constitutiva',
            'poder_notarial' => 'Poder Notarial',
            'rfc' => 'Constancia de Situación Fiscal (RFC)',
            'comprobante_domicilio_fiscal' => 'Comprobante de Domicilio Fiscal',
            'identificacion_representante' => 'Identificación del Representante Legal',
            'otro' => 'Otro',
        ];
    }
}

