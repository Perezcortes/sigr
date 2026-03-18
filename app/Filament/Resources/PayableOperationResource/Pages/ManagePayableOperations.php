<?php

namespace App\Filament\Resources\PayableOperationResource\Pages;

use App\Filament\Resources\PayableOperationResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManagePayableOperations extends ManageRecords
{
    protected static string $resource = PayableOperationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
