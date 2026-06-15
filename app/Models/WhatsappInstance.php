<?php

namespace App\Models;

use WallaceMartinss\FilamentEvolution\Models\WhatsappInstance as BaseWhatsappInstance;

/**
 * Extensión del modelo del paquete para incluir columnas añadidas en migraciones de la app.
 */
class WhatsappInstance extends BaseWhatsappInstance
{
    public function getFillable(): array
    {
        return array_merge(parent::getFillable(), [
            'qr_code_updated_at',
        ]);
    }
}
