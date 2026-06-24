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

    protected function getRedirectUrl(): string
    {
        return \App\Filament\Resources\RentResource::getUrl('view', [
            'record' => $this->record->rent->hash_id ?? $this->record->rent_id,
        ]) . '?tab=-solicitudes-tab&solicitud=-fiador-tab';
    }
}
