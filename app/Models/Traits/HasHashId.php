<?php

namespace App\Models\Traits;

use Hashids\Hashids;
use Illuminate\Database\Eloquent\Model;

trait HasHashId
{
    /**
     * Obtiene el hash del ID del modelo
     */
    public function getHashIdAttribute(): string
    {
        return static::encodeId($this->id);
    }

    /**
     * Codifica un ID a hash
     */
    public static function encodeId(int $id): string
    {
        $salt = config('app.key') ?? 'salt-de-rentas'; 
        $hashids = new Hashids($salt, 8); // Mínimo 8 caracteres
        return $hashids->encode($id);
    }

    /**
     * Decodifica un hash a ID
     */
    public static function decodeId(string $hash): ?int
    {
        $salt = config('app.key') ?? 'salt-de-rentas';
        $hashids = new Hashids($salt, 8);
        $decoded = $hashids->decode($hash);
        
        return !empty($decoded) ? $decoded[0] : null;
    }

    /**
     * Encuentra un modelo por su hash
     */
    public static function findByHash(string $hash): ?static
    {
        $id = static::decodeId($hash);
        
        if (!$id) {
            return null;
        }

        return static::find($id);
    }

    /**
     * Obtiene la clave de ruta usando el hash en lugar del ID
     * (Esto genera la URL tipo: /admin/rentas/M83g8vZg)
     */
    public function getRouteKey(): string
    {
        return $this->hash_id;
    }

    /**
     * Le dice a Laravel cómo buscar el registro cuando recibe un hash.
     */
    /**
     * Esta función INTERCEPTA la búsqueda de Filament.
     * En lugar de buscar "WHERE hash_id = ...", decodifica y busca por ID.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        // dd('Entró al resolveRouteBinding', $value, $field);

        // Si Filament nos pide buscar por 'hash_id' O si el valor no es numérico...
        if ($field === 'hash_id' || !is_numeric($value)) {
            
            // Intentamos decodificar
            $decoded = static::decodeId($value);

            // Si falla la decodificación (es null o array vacío), abortamos
            if (empty($decoded)) {
                abort(404);
            }

            // Obtenemos el ID real (entero)
            // decodeId devuelve int o null en tu trait anterior
            $id = $decoded; 
            
            // Buscamos en la BD usando el ID REAL
            return $this->where('id', $id)->firstOrFail();
        }

        // Fallback: Si por alguna razón llega un ID normal
        return parent::resolveRouteBinding($value, $field);
    }

    // public function getRouteKeyName(): string
    // {
    //    return 'hash_id'; 
    // }
}