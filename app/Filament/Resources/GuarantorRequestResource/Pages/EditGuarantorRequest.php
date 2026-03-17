<?php

namespace App\Filament\Resources\GuarantorRequestResource\Pages;

use App\Filament\Resources\GuarantorRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGuarantorRequest extends EditRecord
{
    protected static string $resource = GuarantorRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
