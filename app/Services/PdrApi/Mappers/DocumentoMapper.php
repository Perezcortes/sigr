<?php

namespace App\Services\PdrApi\Mappers;

class DocumentoMapper
{
    /**
     * Mapea y traduce documentos al formato estricto de PDR.
     * * @param \Illuminate\Support\Collection|null $documents Colección de documentos
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
            
            $nombreTraducido = self::traducirNombre($doc->tag, $rol);

            // 'tag' exacto que exige Jona
            // Inquilino y Fiador usan siempre 'PF' o 'PM'
            // Propietario tiene etiquetas especiales en las reglas de Jona
            $tagAsignado = $tipoPersona; 
            
            if ($rol === 'Propietario') {
                if (str_contains(strtolower($nombreTraducido), 'inmueble') || str_contains(strtolower($nombreTraducido), 'propiedad') || str_contains(strtolower($nombreTraducido), 'predial')) {
                    $tagAsignado = 'Prop';
                } elseif (str_contains(strtolower($nombreTraducido), 'representante')) {
                    $tagAsignado = 'RL'; // O 'Rep legal' según  Jona
                }
            }
            
            return [
                'mime'      => $doc->mime ?? 'application/octet-stream',
                'path_file' => $urlCompleta,
                'tag'       => $tagAsignado,
                'name'      => $nombreTraducido, 
            ];
        })->values()->toArray();
    }

    /**
     * Intenta mapear el nombre guardado en BD al string exacto que exige Jona
     */
    private static function traducirNombre(string $tagGuardado, string $rol): string
    {
        $lowerTag = strtolower($tagGuardado);

        if (str_contains($lowerTag, 'identificaci') && !str_contains($lowerTag, 'representante')) {
            return 'Identificación oficial';
        }
        if (str_contains($lowerTag, 'comprobante de domicilio') && !str_contains($lowerTag, 'representante') && !str_contains($lowerTag, 'propiedad')) {
            return 'Comprobante de domicilio';
        }
        if (str_contains($lowerTag, 'ingresos')) {
            return 'Comprobante de ingresos';
        }
        if (str_contains($lowerTag, 'acta')) {
            return 'Acta constitutiva';
        }
        if (str_contains($lowerTag, 'situaci') || str_contains($lowerTag, 'fiscal')) {
            return 'Constancia de situación fiscal';
        }
        
        // Específicos Fiador y Propietario
        if (str_contains($lowerTag, 'escritura') || str_contains($lowerTag, 'título')) {
            return $rol === 'Fiador' ? 'Escritura/Título Propiedad' : 'Título de propiedad';
        }
        if (str_contains($lowerTag, 'comprobante') && str_contains($lowerTag, 'propiedad')) {
            return $rol === 'Fiador' ? 'Comprobante de Domicilio Propiedad' : 'Comprobante de domicilio propiedad';
        }
        if (str_contains($lowerTag, 'boleta') || str_contains($lowerTag, 'predial')) {
            return $rol === 'Fiador' ? 'Boleta Predial Propiedad' : 'Boleta Predial';
        }
        if (str_contains($lowerTag, 'reglamento')) {
            return $rol === 'Fiador' ? 'Reglamento Propiedad' : 'Reglamento de propiedad';
        }
        if (str_contains($lowerTag, 'foto')) {
            return $rol === 'Fiador' ? 'Foto de la Propiedad' : 'Foto del inmueble';
        }

        // Específicos Representante Legal
        if (str_contains($lowerTag, 'identificaci') && str_contains($lowerTag, 'rep')) {
            return $rol === 'Propietario' ? 'Identificación representante legal' : 'Identificación rep legal';
        }

        return $tagGuardado;
    }
}