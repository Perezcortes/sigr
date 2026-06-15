<?php

namespace App\Filament\Resources\WhatsappInstanceResource\Pages;

use App\Filament\Resources\WhatsappInstanceResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use WallaceMartinss\FilamentEvolution\Enums\StatusConnectionEnum;
use WallaceMartinss\FilamentEvolution\Exceptions\EvolutionApiException;
use WallaceMartinss\FilamentEvolution\Services\EvolutionClient;

class ViewWhatsappInstance extends ViewRecord
{
    protected static string $resource = WhatsappInstanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('connect')
                ->label(__('filament-evolution::resource.actions.connect'))
                ->icon('heroicon-o-qr-code')
                ->color('success')
                ->visible(fn () => $this->record->status !== StatusConnectionEnum::OPEN)
                ->modalHeading(__('filament-evolution::resource.actions.view_qrcode'))
                ->modalContent(fn () => view('filament-evolution::components.qr-code-modal', [
                    'instance' => $this->record,
                ]))
                ->modalWidth('md')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel(__('filament-evolution::resource.actions.close')),

            Actions\Action::make('disconnect')
                ->label(__('filament-evolution::resource.actions.disconnect'))
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => $this->record->status === StatusConnectionEnum::OPEN)
                ->requiresConfirmation()
                ->action(function () {
                    try {
                        $client = app(EvolutionClient::class);
                        $client->logoutInstance($this->record->name);

                        $this->record->update([
                            'status' => StatusConnectionEnum::CLOSE,
                        ]);

                        Notification::make()
                            ->success()
                            ->title(__('filament-evolution::resource.messages.disconnected'))
                            ->send();
                    } catch (EvolutionApiException $e) {
                        Notification::make()
                            ->danger()
                            ->title(__('filament-evolution::resource.messages.connection_failed'))
                            ->body($e->getMessage())
                            ->send();
                    }
                }),

            Actions\Action::make('refresh')
                ->label(__('filament-evolution::resource.actions.refresh'))
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(function () {
                    try {
                        $client = app(EvolutionClient::class);
                        $instances = $client->fetchInstance($this->record->name);

                        if (empty($instances)) {
                            $client->createInstance(
                                instanceName: $this->record->name,
                                number: $this->record->number,
                                qrcode: false
                            );

                            Notification::make()
                                ->success()
                                ->title('Instancia creada en Evolution API')
                                ->send();

                            return;
                        }

                        $instanceData = is_array($instances) ? ($instances[0] ?? $instances) : $instances;
                        $profilePictureUrl = $instanceData['profilePicUrl']
                            ?? $instanceData['instance']['profilePicUrl']
                            ?? null;

                        $state = $client->getConnectionState($this->record->name);
                        $connectionState = $state['state'] ?? $state['instance']['state'] ?? 'close';
                        $status = match (strtolower((string) $connectionState)) {
                            'open', 'connected' => StatusConnectionEnum::OPEN,
                            'connecting' => StatusConnectionEnum::CONNECTING,
                            default => StatusConnectionEnum::CLOSE,
                        };

                        $this->record->update([
                            'status' => $status,
                            'profile_picture_url' => $profilePictureUrl,
                        ]);

                        Notification::make()
                            ->success()
                            ->title(__('filament-evolution::resource.fields.status').': '.$status->getLabel())
                            ->send();
                    } catch (EvolutionApiException $e) {
                        if (str_contains($e->getMessage(), 'Not Found') || $e->getCode() === 404) {
                            try {
                                $client = app(EvolutionClient::class);
                                $client->createInstance(
                                    instanceName: $this->record->name,
                                    number: $this->record->number,
                                    qrcode: false
                                );

                                Notification::make()
                                    ->success()
                                    ->title('Instancia creada en Evolution API')
                                    ->send();

                                return;
                            } catch (EvolutionApiException $createError) {
                                Notification::make()
                                    ->danger()
                                    ->title('No se pudo crear la instancia')
                                    ->body($createError->getMessage())
                                    ->send();

                                return;
                            }
                        }

                        Notification::make()
                            ->danger()
                            ->title(__('filament-evolution::resource.messages.connection_failed'))
                            ->body($e->getMessage())
                            ->send();
                    }
                }),

            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
