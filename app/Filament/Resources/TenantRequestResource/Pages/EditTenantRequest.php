<?php

namespace App\Filament\Resources\TenantRequestResource\Pages;

use App\Filament\Resources\TenantRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTenantRequest extends EditRecord
{
    protected static string $resource = TenantRequestResource::class;

    public function mount($record): void
    {
        parent::mount($record);

        // Pre-cargar datos del tenant si existen
        if ($this->record->tenant) {
            $tenant = $this->record->tenant;
            
            $this->form->fill([
                'tipo_persona' => $tenant->tipo_persona,
                'nombres' => $tenant->nombres,
                'primer_apellido' => $tenant->primer_apellido,
                'segundo_apellido' => $tenant->segundo_apellido,
                'email' => $tenant->email,
                'email_confirmacion' => $tenant->email_confirmacion,
                'telefono_celular' => $tenant->telefono_celular,
                'telefono_fijo' => $tenant->telefono_fijo,
                'nacionalidad' => $tenant->nacionalidad,
                'nacionalidad_especifica' => $tenant->nacionalidad_especifica,
                'sexo' => $tenant->sexo,
                'estado_civil' => $tenant->estado_civil,
                'tipo_identificacion' => $tenant->tipo_identificacion,
                'fecha_nacimiento' => $tenant->fecha_nacimiento,
                'rfc' => $tenant->rfc,
                'curp' => $tenant->curp,
                'conyuge_nombres' => $tenant->conyuge_nombres,
                'conyuge_primer_apellido' => $tenant->conyuge_primer_apellido,
                'conyuge_segundo_apellido' => $tenant->conyuge_segundo_apellido,
                'conyuge_telefono' => $tenant->conyuge_telefono,
                'razon_social' => $tenant->razon_social,
                'dominio_internet' => $tenant->dominio_internet,
                'telefono' => $tenant->telefono,
                'calle' => $tenant->calle,
                'numero_exterior' => $tenant->numero_exterior,
                'numero_interior' => $tenant->numero_interior,
                'codigo_postal' => $tenant->cp,
                'colonia' => $tenant->colonia,
                'municipio' => $tenant->municipio,
                'estado' => $tenant->estado,
                'ingreso_mensual_promedio' => $tenant->ingreso_mensual_promedio,
                'referencias_ubicacion' => $tenant->referencias_ubicacion,
                'notario_nombres' => $tenant->notario_nombres,
                'notario_primer_apellido' => $tenant->notario_primer_apellido,
                'notario_segundo_apellido' => $tenant->notario_segundo_apellido,
                'numero_escritura' => $tenant->numero_escritura,
                'fecha_constitucion' => $tenant->fecha_constitucion,
                'notario_numero' => $tenant->notario_numero,
                'ciudad_registro' => $tenant->ciudad_registro,
                'estado_registro' => $tenant->estado_registro,
                'numero_registro_inscripcion' => $tenant->numero_registro_inscripcion,
                'giro_comercial' => $tenant->giro_comercial,
                'apoderado_nombres' => $tenant->apoderado_nombres,
                'apoderado_primer_apellido' => $tenant->apoderado_primer_apellido,
                'apoderado_segundo_apellido' => $tenant->apoderado_segundo_apellido,
                'apoderado_sexo' => $tenant->apoderado_sexo,
                'apoderado_telefono' => $tenant->apoderado_telefono,
                'apoderado_extension' => $tenant->apoderado_extension,
                'apoderado_email' => $tenant->apoderado_email,
                'facultades_en_acta' => $tenant->facultades_en_acta,
                'escritura_publica_numero' => $tenant->escritura_publica_numero,
                'notario_numero_facultades' => $tenant->notario_numero_facultades,
                'fecha_escritura_facultades' => $tenant->fecha_escritura_facultades,
                'numero_inscripcion_registro_publico' => $tenant->numero_inscripcion_registro_publico,
                'ciudad_registro_facultades' => $tenant->ciudad_registro_facultades,
                'estado_registro_facultades' => $tenant->estado_registro_facultades,
                'fecha_inscripcion_facultades' => $tenant->fecha_inscripcion_facultades,
                'tipo_representacion' => $tenant->tipo_representacion,
            ]);
        }
    }

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
        // Sincronizar datos con la tabla tenants
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
}