<?php

namespace App\Filament\Resources\SolicitudesPolizaLogResource\Pages;

use App\Filament\Resources\SolicitudesPolizaLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSolicitudesPolizaLogs extends ListRecords
{
    protected static string $resource = SolicitudesPolizaLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
