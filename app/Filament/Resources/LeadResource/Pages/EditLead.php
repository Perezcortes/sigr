<?php

namespace App\Filament\Resources\LeadResource\Pages;

use App\Filament\Resources\LeadResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\Tenant;
use App\Models\Owner;
use App\Models\Sale;
use Filament\Notifications\Notification;

class EditLead extends EditRecord
{
    protected static string $resource = LeadResource::class;

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
            }
            elseif (count($partes) === 2) {
                $nombres = $partes[0];
                $paterno = $partes[1];
            }
            else {
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

                if (!$tenant) {
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
                return redirect()->to(\App\Filament\Resources\TenantResource::getUrl('edit', ['record' => $tenant]));
            }

            if ($lead->tipo_cliente === 'arrendador') {
                $owner = Owner::where('email', $lead->correo ?? '')
                    ->where('nombres', $nombres)
                    ->where('primer_apellido', $paterno)
                    ->first();

                if (!$owner) {
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
                return redirect()->to(\App\Filament\Resources\OwnerResource::getUrl('edit', ['record' => $owner]));
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
                }
                else {
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

                if (!$sale) {
                    $sale = Sale::create($data);
                }
                $lead->update(['etapa' => 'ganado']);
                Notification::make()->success()->title('Venta creada (' . ucfirst($lead->tipo_cliente) . ')')->send();
                return redirect()->to(\App\Filament\Resources\SaleResource::getUrl('edit', ['record' => $sale]));
            }

            Notification::make()->warning()->title('Tipo de cliente (' . $lead->tipo_cliente . ') no es convertible automáticamente')->send();
        })->visible(fn() => in_array($this->record->tipo_cliente, ['inquilino', 'arrendador', 'comprador', 'vendedor']) && $this->record->etapa !== 'ganado'),

            Actions\DeleteAction::make(),
        ];
    }
}