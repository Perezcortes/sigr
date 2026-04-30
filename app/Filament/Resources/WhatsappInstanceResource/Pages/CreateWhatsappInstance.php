<?php

namespace App\Filament\Resources\WhatsappInstanceResource\Pages;

use App\Filament\Resources\WhatsappInstanceResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use WallaceMartinss\FilamentEvolution\Enums\StatusConnectionEnum;
use WallaceMartinss\FilamentEvolution\Exceptions\EvolutionApiException;
use WallaceMartinss\FilamentEvolution\Services\EvolutionClient;

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
            $client = app(EvolutionClient::class);
            $client->createInstance(
                instanceName: $this->record->name,
                number: $this->record->number,
                qrcode: false,
                options: $this->getInstanceOptions()
            );

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

    protected function getInstanceOptions(): array
    {
        return [
            'reject_call' => (bool) $this->record->reject_call,
            'msg_call' => $this->record->msg_call ?? '',
            'groups_ignore' => (bool) $this->record->groups_ignore,
            'always_online' => (bool) $this->record->always_online,
            'read_messages' => (bool) $this->record->read_messages,
            'read_status' => (bool) $this->record->read_status,
            'sync_full_history' => (bool) $this->record->sync_full_history,
        ];
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
