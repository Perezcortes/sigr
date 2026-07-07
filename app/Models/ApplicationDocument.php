<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationDocument extends Model
{
    protected $fillable = [
        'application_id',
        'user_id',
        'mime',
        'path_file',
        'tag',
        'user_name',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
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

    /**
     * Tipos de documentos del Fiador (tag 'fiador_...', separado de los del inquilino)
     */
    public static function tiposFiador(): array
    {
        return [
            'fiador_ine_frente' => 'INE del Fiador (Frente)',
            'fiador_ine_reverso' => 'INE del Fiador (Reverso)',
            'fiador_comprobante_domicilio' => 'Comprobante de Domicilio del Fiador',
            'fiador_comprobante_ingresos' => 'Comprobante de Ingresos del Fiador',
            'fiador_titulo_propiedad' => 'Título de Propiedad (Garantía)',
        ];
    }

    /**
     * Documento extra de la solicitud comercial (tag separado, no afecta el selector de Filament)
     */
    public static function tiposComercial(): array
    {
        return [
            'acta_constitutiva_comercial' => 'Acta Constitutiva',
        ];
    }
}
