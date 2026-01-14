<?php

namespace App\Filament\Resources\LeadResource\Pages;

use App\Filament\Resources\LeadResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Actions;
use App\Models\Tenant;
use App\Models\Owner;
use App\Models\Sale; 
use Filament\Notifications\Notification;

class CreateLead extends CreateRecord
{
    protected static string $resource = LeadResource::class;

    // Esta función se ejecuta automáticamente DESPUÉS de guardar el Lead
    protected function afterCreate(): void
    {
        $lead = $this->record;

        try {
            // === CASO 1: INQUILINO ===
            if ($lead->tipo_transaccion === 'inquilino') {
                Tenant::create([
                    'nombres'          => $lead->nombre,   
                    'email'            => $lead->correo,
                    'telefono_celular' => $lead->telefono,
                    'tipo_persona'     => 'fisica',
                    'estatus'          => 'activo',
                ]);
                
                Notification::make()->success()->title('Inquilino creado automáticamente')->send();
            }

            // === CASO 2: PROPIETARIO ===
            elseif ($lead->tipo_transaccion === 'propietario') {
                Owner::create([
                    'nombres'          => $lead->nombre,
                    'email'            => $lead->correo,
                    'telefono'         => $lead->telefono, 
                    'tipo_persona'     => 'fisica',
                    'estatus'          => 'activo',
                ]);

                Notification::make()->success()->title('Propietario creado automáticamente')->send();
            }

            // === CASO 3: VENTA (SALE) ===
            elseif ($lead->tipo_transaccion === 'venta') {
                Sale::create([
                    'nombre_cliente_principal' => $lead->nombre, 
                    'comprador_email'          => $lead->correo,
                    'comprador_telefono'       => $lead->telefono,
                    
                    // Datos obligatorios extra
                    'fecha_inicio'             => now(),
                    'estatus_hipoteca'                  => 'prospecto',
                    'comprador_nombres'        => $lead->nombre, 
                ]);

                Notification::make()->success()->title('Proceso de venta iniciado')->send();
            }

        } catch (\Exception $e) {
            Notification::make()
                ->warning()
                ->title('Error en creación automática')
                ->body('Error SQL: ' . $e->getMessage())
                ->persistent()
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}