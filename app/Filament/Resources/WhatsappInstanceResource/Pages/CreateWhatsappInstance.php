<?php

namespace App\Filament\Resources\WhatsappInstanceResource\Pages;

use App\Filament\Resources\WhatsappInstanceResource;
use App\Services\EvolutionInstanceBootstrapper;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use WallaceMartinss\FilamentEvolution\Enums\StatusConnectionEnum;
use WallaceMartinss\FilamentEvolution\Exceptions\EvolutionApiException;

class CreateWhatsappInstance extends CreateRecord
{
    protected static string $resource = WhatsappInstanceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = StatusConnectionEnum::CLOSE;

        return $data;
    }

    protected function afterCreate(): void
    {
        try {
            app(EvolutionInstanceBootstrapper::class)->syncInstanceToEvolutionApi($this->record);

            Notification::make()
                ->success()
                ->title(__('filament-evolution::resource.messages.created'))
                ->body('Instancia creada en Evolution API')
                ->send();
        } catch (EvolutionApiException $e) {
            Notification::make()
                ->warning()
                ->title(__('filament-evolution::resource.messages.created'))
                ->body('Guardado local. Error al sincronizar con la API: '.$e->getMessage())
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index', ['connectInstanceId' => (string) $this->record->id]);
    }

    protected function getCreatedNotification(): ?Notification
    {
        return null;
    }
}
