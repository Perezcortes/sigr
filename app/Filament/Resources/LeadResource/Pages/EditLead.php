<?php

namespace App\Filament\Resources\LeadResource\Pages;

use App\Filament\Resources\LeadResource;
use App\Filament\Resources\OwnerResource;
use App\Filament\Resources\SaleResource;
use App\Filament\Resources\TenantResource;
use App\Models\LeadActivity;
use App\Models\Owner;
use App\Models\Sale;
use App\Models\Tenant;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditLead extends EditRecord
{
    protected static string $resource = LeadResource::class;

    public int $seguimientoListKey = 0;

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
            Actions\Action::make('procesar')
                ->label('Procesar Prospecto')
                ->color('success')
                ->icon('heroicon-o-arrow-path-rounded-square')
                ->requiresConfirmation()
                ->modalHeading('Convertir Prospecto')
                ->modalDescription('¿Estás seguro que deseas procesar este prospecto? Se creará el registro final según su tipo de operación y este lead pasará a etapa "Ganado".')
                ->action(function () {
                    $lead = $this->record;
                    $nombres = '';
                    $paterno = '';
                    $materno = '';

                    // Separar el nombre en partes (Básico: Nombre Apellido1 Apellido2)
                    $partes = explode(' ', trim($lead->nombre));
                    if (count($partes) === 1) {
                        $nombres = $partes[0];
                    } elseif (count($partes) === 2) {
                        $nombres = $partes[0];
                        $paterno = $partes[1];
                    } else {
                        // Toma el primero como nombre, el penúltimo como paterno, el último como materno.
                        // Todo lo del centro se suma al nombre.
                        $materno = array_pop($partes);
                        $paterno = array_pop($partes);
                        $nombres = implode(' ', $partes);
                    }

                    if ($lead->tipo_cliente === 'inquilino') {
                        $tenant = Tenant::where('email', $lead->correo ?? '')
                            ->where('nombres', $nombres)
                            ->where('primer_apellido', $paterno)
                            ->first();

                        if (! $tenant) {
                            $tenant = Tenant::create([
                                'tipo_persona' => 'fisica',
                                'nombres' => $nombres,
                                'primer_apellido' => $paterno,
                                'segundo_apellido' => $materno,
                                'email' => $lead->correo ?? '',
                                'telefono_celular' => $lead->telefono ?? '',
                                'historial_acciones' => $lead->historial_acciones,
                                'estado_civil' => 'soltero',
                                'nacionalidad' => 'mexicana',
                                'sexo' => 'masculino',
                                'tipo_identificacion' => 'INE',
                                'asesor_id' => $lead->responsable_id,
                            ]);
                        }

                        $lead->update(['etapa' => 'ganado']);
                        Notification::make()->success()->title('Inquilino creado')->send();

                        return redirect()->to(TenantResource::getUrl('edit', ['record' => $tenant]));
                    }

                    if ($lead->tipo_cliente === 'arrendador') {
                        $owner = Owner::where('email', $lead->correo ?? '')
                            ->where('nombres', $nombres)
                            ->where('primer_apellido', $paterno)
                            ->first();

                        if (! $owner) {
                            $owner = Owner::create([
                                'tipo_persona' => 'fisica',
                                'nombres' => $nombres,
                                'primer_apellido' => $paterno,
                                'segundo_apellido' => $materno,
                                'email' => $lead->correo ?? '',
                                'telefono' => $lead->telefono ?? '',
                                'historial_acciones' => $lead->historial_acciones,
                                'estado_civil' => 'Soltero',
                                'sexo' => 'Masculino',
                                'nacionalidad' => 'Mexicana',
                                'tipo_identificacion' => 'INE',
                                'asesor_id' => $lead->responsable_id,
                            ]);
                        }

                        $lead->update(['etapa' => 'ganado']);
                        Notification::make()->success()->title('Arrendador creado')->send();

                        return redirect()->to(OwnerResource::getUrl('edit', ['record' => $owner]));
                    }

                    if (in_array($lead->tipo_cliente, ['comprador', 'vendedor'])) {
                        $data = [
                            'estatus_operacion' => 'Activa',
                        ];

                        if ($lead->tipo_cliente === 'comprador') {
                            $data['nombre_cliente_principal'] = $lead->nombre;
                            $data['comprador_nombres'] = $nombres;
                            $data['comprador_ap_paterno'] = $paterno;
                            $data['comprador_ap_materno'] = $materno;
                            $data['comprador_email'] = $lead->correo;
                            $data['comprador_celular'] = $lead->telefono;

                            $sale = Sale::where('comprador_email', $lead->correo ?? '')
                                ->where('comprador_nombres', $nombres)
                                ->first();
                        } else {
                            $data['nombre_cliente_principal'] = $lead->nombre;
                            $data['vendedor_nombres'] = $nombres;
                            $data['vendedor_ap_paterno'] = $paterno;
                            $data['vendedor_ap_materno'] = $materno;
                            $data['vendedor_email'] = $lead->correo;
                            $data['vendedor_celular'] = $lead->telefono;

                            $sale = Sale::where('vendedor_email', $lead->correo ?? '')
                                ->where('vendedor_nombres', $nombres)
                                ->first();
                        }

                        if (! $sale) {
                            $sale = Sale::create($data);
                        }
                        $lead->update(['etapa' => 'ganado']);
                        Notification::make()->success()->title('Venta creada ('.ucfirst($lead->tipo_cliente).')')->send();

                        return redirect()->to(SaleResource::getUrl('edit', ['record' => $sale]));
                    }

                    Notification::make()->warning()->title('Tipo de cliente ('.$lead->tipo_cliente.') no es convertible automáticamente')->send();
                })->visible(fn () => in_array($this->record->tipo_cliente, ['inquilino', 'arrendador', 'comprador', 'vendedor']) && $this->record->etapa !== 'ganado'),

            Actions\DeleteAction::make(),
        ];
    }
}
