<?php

namespace App\Filament\Resources\TenantRequestResource\Pages;

use App\Filament\Resources\TenantRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTenantRequest extends EditRecord
{
    protected static string $resource = TenantRequestResource::class;

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

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->record->tenant) {
            $tenantData = [
                'tipo_persona' => $data['tipo_persona'],
                'nombres' => $data['nombres'] ?? null,
                'primer_apellido' => $data['primer_apellido'] ?? null,
                'segundo_apellido' => $data['segundo_apellido'] ?? null,
                'email' => $data['email'] ?? null,
                'email_confirmacion' => $data['email_confirmacion'] ?? null,
                'telefono_celular' => $data['telefono_celular'] ?? null,
                'telefono_fijo' => $data['telefono_fijo'] ?? null,
                'nacionalidad' => $data['nacionalidad'] ?? null,
                'nacionalidad_especifica' => $data['nacionalidad_especifica'] ?? null,
                'sexo' => $data['sexo'] ?? null,
                'estado_civil' => $data['estado_civil'] ?? null,
                'tipo_identificacion' => $data['tipo_identificacion'] ?? null,
                'fecha_nacimiento' => $data['fecha_nacimiento'] ?? null,
                'rfc' => $data['rfc'] ?? null,
                'curp' => $data['curp'] ?? null,
                'conyuge_nombres' => $data['conyuge_nombres'] ?? null,
                'conyuge_primer_apellido' => $data['conyuge_primer_apellido'] ?? null,
                'conyuge_segundo_apellido' => $data['conyuge_segundo_apellido'] ?? null,
                'conyuge_telefono' => $data['conyuge_telefono'] ?? null,
                'razon_social' => $data['razon_social'] ?? null,
                'dominio_internet' => $data['dominio_internet'] ?? null,
                'telefono' => $data['telefono'] ?? null,
                'calle' => $data['calle'] ?? null,
                'numero_exterior' => $data['numero_exterior'] ?? null,
                'numero_interior' => $data['numero_interior'] ?? null,
                'cp' => $data['codigo_postal'] ?? null,
                'colonia' => $data['colonia'] ?? null,
                'municipio' => $data['municipio'] ?? null,
                'estado' => $data['estado'] ?? null,
                'ingreso_mensual_promedio' => $data['ingreso_mensual_promedio'] ?? null,
                'referencias_ubicacion' => $data['referencias_ubicacion'] ?? null,
                'notario_nombres' => $data['notario_nombres'] ?? null,
                'notario_primer_apellido' => $data['notario_primer_apellido'] ?? null,
                'notario_segundo_apellido' => $data['notario_segundo_apellido'] ?? null,
                'numero_escritura' => $data['numero_escritura'] ?? null,
                'fecha_constitucion' => $data['fecha_constitucion'] ?? null,
                'notario_numero' => $data['notario_numero'] ?? null,
                'ciudad_registro' => $data['ciudad_registro'] ?? null,
                'estado_registro' => $data['estado_registro'] ?? null,
                'numero_registro_inscripcion' => $data['numero_registro_inscripcion'] ?? null,
                'giro_comercial' => $data['giro_comercial'] ?? null,
                'apoderado_nombres' => $data['apoderado_nombres'] ?? null,
                'apoderado_primer_apellido' => $data['apoderado_primer_apellido'] ?? null,
                'apoderado_segundo_apellido' => $data['apoderado_segundo_apellido'] ?? null,
                'apoderado_sexo' => $data['apoderado_sexo'] ?? null,
                'apoderado_telefono' => $data['apoderado_telefono'] ?? null,
                'apoderado_extension' => $data['apoderado_extension'] ?? null,
                'apoderado_email' => $data['apoderado_email'] ?? null,
                'facultades_en_acta' => $data['facultades_en_acta'] ?? false,
                'escritura_publica_numero' => $data['escritura_publica_numero'] ?? null,
                'notario_numero_facultades' => $data['notario_numero_facultades'] ?? null,
                'fecha_escritura_facultades' => $data['fecha_escritura_facultades'] ?? null,
                'numero_inscripcion_registro_publico' => $data['numero_inscripcion_registro_publico'] ?? null,
                'ciudad_registro_facultades' => $data['ciudad_registro_facultades'] ?? null,
                'estado_registro_facultades' => $data['estado_registro_facultades'] ?? null,
                'fecha_inscripcion_facultades' => $data['fecha_inscripcion_facultades'] ?? null,
                'tipo_representacion' => $data['tipo_representacion'] ?? null,
            ];
            
            $this->record->tenant->update($tenantData);
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return \App\Filament\Resources\RentResource::getUrl('view', [
          'record' => $this->record->rent->hash_id ?? $this->record->rent_id,
        ]) . '?tab=-solicitudes-tab&solicitud=-inquilino-tab';
    }
}