<?php

namespace App\Filament\Resources\PropietariosVendedoresResource\Pages;

use App\Filament\Resources\Concerns\HasPortalClientQuickCreate;
use App\Filament\Resources\PropietariosVendedoresResource;
use Filament\Resources\Pages\ListRecords;

class ListPropietariosVendedores extends ListRecords
{
    use HasPortalClientQuickCreate;

    protected static string $resource = PropietariosVendedoresResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->portalClientCreateAction('Propietario / Vendedor'),
        ];
    }
}
