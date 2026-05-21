<?php

namespace App\Filament\Resources\LeadResource\Pages;

use App\Filament\Resources\LeadResource;
use App\Models\LeadActivity;
use App\Services\LeadProspectProcessor;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditLead extends EditRecord
{
    protected static string $resource = LeadResource::class;

    public int $seguimientoListKey = 0;

    protected ?string $prospectProcessRedirectUrl = null;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['seguimiento_draft'] = LeadResource::seguimientoDraftDefaults();
        $data['seguimiento_show_form'] = false;
        $this->seguimientoListKey = 0;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset(
            $data['seguimiento_draft'],
            $data['seguimiento_show_form'],
        );

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->refresh();

        $processor = app(LeadProspectProcessor::class);

        if (! $processor->shouldProcessOnSave($this->record)) {
            return;
        }

        $result = $processor->process($this->record);

        if ($result['notification_title']) {
            $notification = Notification::make()->title($result['notification_title']);

            if ($result['notification_warning']) {
                $notification->warning()->send();
            } else {
                $notification->success()->send();
            }
        }

        if ($result['redirect']) {
            $this->prospectProcessRedirectUrl = $result['redirect'];
        }
    }

    protected function getRedirectUrl(): ?string
    {
        return $this->prospectProcessRedirectUrl ?? parent::getRedirectUrl();
    }

    protected function getSavedNotification(): ?Notification
    {
        if ($this->prospectProcessRedirectUrl !== null) {
            return null;
        }

        return parent::getSavedNotification();
    }

    public function toggleActividadCompletada(int $activityId): void
    {
        $activity = $this->findLeadActivity($activityId);
        $completada = ! $activity->completada;
        $activity->update(['completada' => $completada]);

        $this->refreshSeguimientoLista();

        Notification::make()
            ->success()
            ->title($completada ? 'Actividad marcada como realizada' : 'Actividad marcada como pendiente')
            ->send();
    }

    public function editActividadAction(): Actions\Action
    {
        return Actions\Action::make('editActividad')
            ->label('Editar actividad')
            ->icon('heroicon-m-pencil-square')
            ->modalHeading('Editar actividad')
            ->form(LeadResource::seguimientoActivityEditFormSchema())
            ->fillForm(function (array $arguments): array {
                $activity = $this->findLeadActivity($arguments['activityId']);

                return [
                    'fecha' => $activity->fecha->format('Y-m-d'),
                    'hora' => $activity->hora ?? '09:00',
                    'descripcion' => $activity->descripcion,
                    'completada' => $activity->completada,
                ];
            })
            ->action(function (array $data, array $arguments): void {
                $activity = $this->findLeadActivity($arguments['activityId']);

                $activity->update([
                    'fecha' => $data['fecha'],
                    'hora' => $data['hora'] ?? '09:00',
                    'descripcion' => trim((string) $data['descripcion']),
                    'completada' => (bool) ($data['completada'] ?? false),
                ]);

                $this->refreshSeguimientoLista();

                Notification::make()
                    ->success()
                    ->title('Actividad actualizada')
                    ->send();
            });
    }

    public function deleteActividadAction(): Actions\Action
    {
        return Actions\Action::make('deleteActividad')
            ->label('Eliminar')
            ->icon('heroicon-m-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Eliminar actividad')
            ->modalDescription('¿Seguro que deseas eliminar esta actividad? Esta acción no se puede deshacer.')
            ->modalSubmitActionLabel('Eliminar')
            ->action(function (array $arguments): void {
                $this->findLeadActivity($arguments['activityId'])->delete();

                $this->refreshSeguimientoLista();

                Notification::make()
                    ->success()
                    ->title('Actividad eliminada')
                    ->send();
            });
    }

    protected function findLeadActivity(int $activityId): LeadActivity
    {
        return LeadActivity::query()
            ->where('lead_id', $this->record->id)
            ->findOrFail($activityId);
    }

    public function refreshSeguimientoLista(): void
    {
        $this->record->refresh();
        $this->record->load('activities');
        $this->seguimientoListKey++;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
