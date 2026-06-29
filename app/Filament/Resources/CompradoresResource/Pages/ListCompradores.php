<?php

namespace App\Filament\Resources\CompradoresResource\Pages;

use App\Filament\Resources\CompradoresResource;
use App\Filament\Resources\Concerns\HasPortalClientQuickCreate;
use Filament\Resources\Pages\ListRecords;

class ListCompradores extends ListRecords
{
    use HasPortalClientQuickCreate;

    protected static string $resource = CompradoresResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->portalClientCreateAction('Comprador'),
        ];
    }
}
