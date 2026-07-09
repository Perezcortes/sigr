<?php

namespace App\Filament\Resources\SolicitudesPolizaLogResource\Pages;

use App\Filament\Resources\SolicitudesPolizaLogResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSolicitudesPolizaLog extends EditRecord
{
    protected static string $resource = SolicitudesPolizaLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
