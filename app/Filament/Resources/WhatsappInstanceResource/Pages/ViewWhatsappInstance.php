<?php

namespace App\Filament\Resources\WhatsappInstanceResource\Pages;

use App\Filament\Resources\WhatsappInstanceResource;
use App\Services\OpenWaService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use WallaceMartinss\FilamentEvolution\Enums\StatusConnectionEnum;

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
                ->modalContent(function () {
                    $openWa = app(OpenWaService::class);
                    $qrCode = null;
                    if ($this->record->instance_id) {
                        try {
                            $openWa->startSession($this->record->instance_id);
                            usleep(1000000); // Esperar 1 segundo para inicializar
                            $statusData = $openWa->getSessionStatus($this->record->instance_id);
                            if (($statusData['status'] ?? '') === 'qr_ready') {
                                $qrCode = $openWa->getQrCode($this->record->instance_id);
                            }
                        } catch (\Throwable $e) {}
                    }
                    return view('components.openwa-qr-modal', [
                        'qrCode' => $qrCode,
                        'instance' => $this->record,
                    ]);
                })
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
                        if ($this->record->instance_id) {
                            $openWa = app(OpenWaService::class);
                            $openWa->stopSession($this->record->instance_id);
                        }

                        $this->record->update([
                            'status' => StatusConnectionEnum::CLOSE,
                        ]);

                        Notification::make()
                            ->success()
                            ->title(__('filament-evolution::resource.messages.disconnected'))
                            ->send();
                    } catch (\Throwable $e) {
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
                        $openWa = app(OpenWaService::class);
                        if (!$this->record->instance_id) {
                            $session = $openWa->createSession($this->record->name);
                            $this->record->update([
                                'instance_id' => $session['id'],
                                'status' => StatusConnectionEnum::CONNECTING,
                            ]);
                            $openWa->startSession($session['id']);
                            
                            Notification::make()
                                ->success()
                                ->title('Instancia iniciada en OpenWA')
                                ->send();
                            return;
                        }

                        $statusData = $openWa->getSessionStatus($this->record->instance_id);
                        $openWaStatus = $statusData['status'] ?? 'disconnected';

                        $status = match ($openWaStatus) {
                            'ready' => StatusConnectionEnum::OPEN,
                            'initializing', 'authenticating' => StatusConnectionEnum::CONNECTING,
                            default => StatusConnectionEnum::CLOSE,
                        };

                        $this->record->update([
                            'status' => $status,
                        ]);

                        Notification::make()
                            ->success()
                            ->title(__('filament-evolution::resource.fields.status').': '.$status->getLabel())
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->danger()
                            ->title(__('filament-evolution::resource.messages.connection_failed'))
                            ->body($e->getMessage())
                            ->send();
                    }
                }),

            Actions\EditAction::make(),
            Actions\DeleteAction::make()
                ->after(function () {
                    try {
                        if ($this->record->instance_id) {
                            app(OpenWaService::class)->deleteSession($this->record->instance_id);
                        }
                    } catch (\Throwable $e) {}
                }),
        ];
    }
}
