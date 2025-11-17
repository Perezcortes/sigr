<?php

namespace App\Models\Traits;

use Hashids\Hashids;

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
        $hashids = new Hashids(config('app.key'), 8); // Mínimo 8 caracteres
        return $hashids->encode($id);
    }

    /**
     * Decodifica un hash a ID
     */
    public static function decodeId(string $hash): ?int
    {
        $hashids = new Hashids(config('app.key'), 8);
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
     */
    public function getRouteKey(): string
    {
        return $this->hash_id;
    }

    /**
     * Obtiene el nombre de la clave de ruta (para route model binding)
     */
    public function getRouteKeyName(): string
    {
        return 'hash_id';
    }
}

