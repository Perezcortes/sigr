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
            Actions\Action::make('guardar_continuar')
                ->label('Guardar y continuar después')
                ->color('gray')
                ->action('save'),
                
            Actions\Action::make('enviar_revision')
                ->label('Enviar a revisión')
                ->color('success')
                ->visible(fn () => $this->record->estatus === 'nueva')
                ->action(function () {
                    $this->record->update(['estatus' => 'en_proceso']);
                    $this->refreshFormData(['estatus' => 'en_proceso']);
                    
                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title('Solicitud enviada a revisión')
                        ->send();
                }),
                
            Actions\Action::make('volver')
                ->label('Volver a la renta')
                ->color('gray')
                ->url(fn () => \App\Filament\Resources\RentResource::getUrl('view', ['record' => $this->record->rent->hash_id])),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return \App\Filament\Resources\RentResource::getUrl('view', [
            'record' => $this->record->rent->hash_id ?? $this->record->rent_id,
        ]) . '?tab=-solicitudes-tab&solicitud=-fiador-tab';
    }
}