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

        if ($id === null) {
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
     * Filament (y otras rutas) resuelven el registro vía {@see resolveRouteBindingQuery},
     * no solo {@see resolveRouteBinding}; hay que decodificar el hash a la PK numérica.
     */
    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        $valueStr = (string) $value;

        if ($field === 'hash_id' || ! is_numeric($valueStr)) {
            $id = static::decodeId($valueStr);

            if ($id === null) {
                return $query->whereRaw('0 = 1');
            }

            return $query->where($this->getQualifiedKeyName(), $id);
        }

        return parent::resolveRouteBindingQuery($query, $value, $field);
    }

    /**
     * Resolución por hash para enlaces HTTP que usan el binding clásico de Laravel.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        if ($field === 'hash_id' || ! is_numeric((string) $value)) {
            $id = static::decodeId((string) $value);

            if ($id === null) {
                abort(404);
            }

            return $this->where($this->getQualifiedKeyName(), $id)->firstOrFail();
        }

        return parent::resolveRouteBinding($value, $field);
    }

    // public function getRouteKeyName(): string
    // {
    //    return 'hash_id'; 
    // }
}