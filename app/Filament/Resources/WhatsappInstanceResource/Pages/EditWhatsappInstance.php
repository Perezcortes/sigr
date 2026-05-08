<?php

namespace App\Filament\Resources\WhatsappInstanceResource\Pages;

use App\Filament\Resources\WhatsappInstanceResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditWhatsappInstance extends EditRecord
{
    protected static string $resource = WhatsappInstanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title(__('filament-evolution::resource.messages.updated'));
    }
}
