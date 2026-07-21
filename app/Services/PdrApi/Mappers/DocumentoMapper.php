<?php

namespace App\Services\PdrApi\Mappers;

class DocumentoMapper
{
    /**
     * Mapea y traduce documentos al formato estricto de PDR.
     *
     * @param \Illuminate\Support\Collection|null $documents Colección de documentos
     * @param string $rol 'Inquilino', 'Fiador' o 'Propietario'
     * @param string $tipoPersona 'PF' o 'PM'
     * @return array
     */
    public static function mapear($documents, string $rol, string $tipoPersona): array
    {
        if (!$documents || $documents->isEmpty()) {
            return [];
        }

        return $documents->map(function ($doc) use ($rol, $tipoPersona) {
            // Obtenemos la URL completa
            $urlCompleta = asset('storage/' . $doc->path_file);

            // Obtenemos el nombre traducido al formato exacto de Jona
            // Usamos $doc->name (o el campo que uses para el nombre real) en lugar del tag
            $nombreBase = $doc->name ?? $doc->tag; 
            $nombreTraducido = self::traducirNombre($nombreBase, $rol);

            // 'tag' exacto 
            $tagAsignado = $tipoPersona;

            if ($rol === 'Propietario') {
                $nombreLower = strtolower($nombreTraducido);
                if (str_contains($nombreLower, 'propiedad') || str_contains($nombreLower, 'inmueble') || str_contains($nombreLower, 'predial')) {
                    $tagAsignado = 'Prop';
                } elseif (str_contains($nombreLower, 'representante legal') || str_contains($nombreLower, 'poder notarial') || str_contains($nombreLower, 'poder_notarial')) {
                    $tagAsignado = 'RL';
                }
            }

            return [
                'mime'      => $doc->mime ?? 'application/pdf',
                'path_file' => $urlCompleta,
                'tag'       => $tagAsignado,
                'name'      => $nombreTraducido,
            ];
        })->filter(function ($docMap) use ($rol) {
            // Filtramos lo que no está en el catálogo de Jona
            if ($rol === 'Propietario') {
                $permitidosJona = [
                    'Identificación oficial', 'Comprobante de domicilio', 'Documento migratorio',
                    'Título de propiedad', 'Comprobante de domicilio propiedad', 'Reglamento de propiedad',
                    'Foto del inmueble', 'Boleta Predial', 'Identificación representante legal',
                    'Comprobante de domicilio representante legal', 'Documento que acredita al representante legal'
                ];
                return in_array($docMap['name'], $permitidosJona);
            }
            return true;
        })->values()->toArray();
    }

    /**
     * Intenta mapear el nombre guardado en BD al string exacto que exige Jona
     */
    private static function traducirNombre(string $nombreOriginal, string $rol): string
    {
        $lowerName = strtolower($nombreOriginal);

        // CONSTANCIA DE SITUACIÓN FISCAL / RFC
        // Si en tu panel lo llaman 'rfc', 'csf' o 'situación', lo mandamos como Constancia
        if (str_contains($lowerName, 'rfc') || str_contains($lowerName, 'situaci') || str_contains($lowerName, 'fiscal')) {
            return 'Constancia de situación fiscal';
        }

        // ACTA CONSTITUTIVA
        if (str_contains($lowerName, 'acta') || str_contains($lowerName, 'constitutiva')) {
            return 'Acta constitutiva';
        }

        // IDENTIFICACIÓN OFICIAL Y REPRESENTANTE LEGAL
        if (str_contains($lowerName, 'identificaci') || str_contains($lowerName, 'ine') || str_contains($lowerName, 'pasaporte')) {
            if (str_contains($lowerName, 'rep') || str_contains($lowerName, 'legal')) {
                return $rol === 'Propietario' ? 'Identificación representante legal' : 'Identificación rep legal';
            }
            return 'Identificación oficial';
        }

        // COMPROBANTES DE DOMICILIO
        if (str_contains($lowerName, 'comprobante') || str_contains($lowerName, 'domicilio') || str_contains($lowerName, 'luz') || str_contains($lowerName, 'agua')) {
            // Si es comprobante de la propiedad en garantía
            if (str_contains($lowerName, 'propiedad')) {
                return $rol === 'Fiador' ? 'Comprobante de Domicilio Propiedad' : 'Comprobante de domicilio propiedad';
            }
            // Si es comprobante del representante legal (Solo propietario)
            if ($rol === 'Propietario' && (str_contains($lowerName, 'rep') || str_contains($lowerName, 'legal'))) {
                return 'Comprobante de domicilio representante legal';
            }
            // Comprobante de domicilio normal
            return 'Comprobante de domicilio';
        }

        // COMPROBANTES DE INGRESOS
        if (str_contains($lowerName, 'ingresos') || str_contains($lowerName, 'estado de cuenta') || str_contains($lowerName, 'nomina')) {
            return 'Comprobante de ingresos';
        }

        // PODER NOTARIAL / DOCUMENTO REPRESENTANTE LEGAL (Solo propietario PM)
        if (str_contains($lowerName, 'poder') || str_contains($lowerName, 'notarial') || str_contains($lowerName, 'acredita')) {
            return 'Documento que acredita al representante legal';
        }

        // ESPECÍFICOS DE LA PROPIEDAD EN GARANTÍA O DEL INMUEBLE RENTADO
        if (str_contains($lowerName, 'escritura') || str_contains($lowerName, 'título') || str_contains($lowerName, 'titulo')) {
            return $rol === 'Fiador' ? 'Escritura/Título Propiedad' : 'Título de propiedad';
        }
        if (str_contains($lowerName, 'boleta') || str_contains($lowerName, 'predial')) {
            return $rol === 'Fiador' ? 'Boleta Predial Propiedad' : 'Boleta Predial';
        }
        if (str_contains($lowerName, 'reglamento')) {
            return $rol === 'Fiador' ? 'Reglamento Propiedad' : 'Reglamento de propiedad';
        }
        if (str_contains($lowerName, 'foto') || str_contains($lowerName, 'imagen')) {
            return $rol === 'Fiador' ? 'Foto de la Propiedad' : 'Foto del inmueble';
        }
        
        // MIGRATORIO (Solo Propietario)
        if (str_contains($lowerName, 'migratorio')) {
            return 'Documento migratorio';
        }

        // Fallback: Si no coincide con nada, devolvemos el original esperando que el usuario lo haya escrito perfecto.
        return $nombreOriginal;
    }
}