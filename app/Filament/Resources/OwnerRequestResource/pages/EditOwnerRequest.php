<?php

namespace App\Filament\Resources\OwnerRequestResource\Pages;

use App\Filament\Resources\OwnerRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOwnerRequest extends EditRecord
{
    protected static string $resource = OwnerRequestResource::class;

    public function mount($record): void
    {
        parent::mount($record);

        // Pre-cargar datos del owner si existen
        if ($this->record->owner) {
            $owner = $this->record->owner;
            
            $this->form->fill([
                'tipo_persona' => $owner->tipo_persona,
                'nombres' => $owner->nombres,
                'primer_apellido' => $owner->primer_apellido,
                'segundo_apellido' => $owner->segundo_apellido,
                'email' => $owner->email,
                'telefono' => $owner->telefono,
                'sexo' => $owner->sexo,
                'estado_civil' => $owner->estado_civil,
                'regimen_conyugal' => $owner->regimen_conyugal,
                'nacionalidad' => $owner->nacionalidad,
                'tipo_identificacion' => $owner->tipo_identificacion,
                'rfc' => $owner->rfc,
                'curp' => $owner->curp,
                'calle' => $owner->calle,
                'numero_exterior' => $owner->numero_exterior,
                'numero_interior' => $owner->numero_interior,
                'codigo_postal' => $owner->codigo_postal,
                'colonia' => $owner->colonia,
                'delegacion_municipio' => $owner->delegacion_municipio,
                'estado' => $owner->estado,
                'referencias_ubicacion' => $owner->referencias_ubicacion,
                'forma_pago' => $owner->forma_pago,
                'forma_pago_otro' => $owner->forma_pago_otro,
                'titular_cuenta' => $owner->titular_cuenta,
                'numero_cuenta' => $owner->numero_cuenta,
                'nombre_banco' => $owner->nombre_banco,
                'clabe_interbancaria' => $owner->clabe_interbancaria,
                'sera_representado' => $owner->sera_representado,
                'tipo_representacion' => $owner->tipo_representacion,
                // Campos para Persona Moral
                'razon_social' => $owner->razon_social,
                'dominio_internet' => $owner->dominio_internet,
                'notario_nombres' => $owner->notario_nombres,
                'notario_primer_apellido' => $owner->notario_primer_apellido,
                'notario_segundo_apellido' => $owner->notario_segundo_apellido,
                'numero_escritura' => $owner->numero_escritura,
                'fecha_constitucion' => $owner->fecha_constitucion,
                'notario_numero' => $owner->notario_numero,
                'ciudad_registro' => $owner->ciudad_registro,
                'estado_registro' => $owner->estado_registro,
                'numero_registro_inscripcion' => $owner->numero_registro_inscripcion,
                'giro_comercial' => $owner->giro_comercial,
                'apoderado_nombres' => $owner->apoderado_nombres,
                'apoderado_primer_apellido' => $owner->apoderado_primer_apellido,
                'apoderado_segundo_apellido' => $owner->apoderado_segundo_apellido,
                'apoderado_sexo' => $owner->apoderado_sexo,
                'apoderado_curp' => $owner->apoderado_curp,
                'apoderado_email' => $owner->apoderado_email,
                'apoderado_telefono' => $owner->apoderado_telefono,
                'apoderado_calle' => $owner->apoderado_calle,
                'apoderado_numero_exterior' => $owner->apoderado_numero_exterior,
                'apoderado_numero_interior' => $owner->apoderado_numero_interior,
                'apoderado_cp' => $owner->apoderado_cp,
                'apoderado_colonia' => $owner->apoderado_colonia,
                'apoderado_municipio' => $owner->apoderado_municipio,
                'apoderado_estado' => $owner->apoderado_estado,
                'facultades_en_acta' => $owner->facultades_en_acta,
                'escritura_publica_numero' => $owner->escritura_publica_numero,
                'notario_numero_facultades' => $owner->notario_numero_facultades,
                'fecha_escritura_facultades' => $owner->fecha_escritura_facultades,
                'numero_inscripcion_registro_publico' => $owner->numero_inscripcion_registro_publico,
                'ciudad_registro_facultades' => $owner->ciudad_registro_facultades,
                'estado_registro_facultades' => $owner->estado_registro_facultades,
                'tipo_representacion_moral' => $owner->tipo_representacion_moral,
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
        // Sincronizar datos con la tabla owners
        if ($this->record->owner) {
            $ownerData = [
                'nombres' => $data['nombres'] ?? null,
                'primer_apellido' => $data['primer_apellido'] ?? null,
                'segundo_apellido' => $data['segundo_apellido'] ?? null,
                'email' => $data['email'] ?? null,
                'telefono' => $data['telefono'] ?? null,
                'estado_civil' => $data['estado_civil'] ?? null,
                'regimen_conyugal' => $data['regimen_conyugal'] ?? null,
                'sexo' => $data['sexo'] ?? null,
                'nacionalidad' => $data['nacionalidad'] ?? null,
                'tipo_identificacion' => $data['tipo_identificacion'] ?? null,
                'rfc' => $data['rfc'] ?? null,
                'curp' => $data['curp'] ?? null,
                'calle' => $data['calle'] ?? null,
                'numero_exterior' => $data['numero_exterior'] ?? null,
                'numero_interior' => $data['numero_interior'] ?? null,
                'codigo_postal' => $data['codigo_postal'] ?? null,
                'colonia' => $data['colonia'] ?? null,
                'delegacion_municipio' => $data['delegacion_municipio'] ?? null,
                'estado' => $data['estado'] ?? null,
                'referencias_ubicacion' => $data['referencias_ubicacion'] ?? null,
                'forma_pago' => $data['forma_pago'] ?? null,
                'forma_pago_otro' => $data['forma_pago_otro'] ?? null,
                'titular_cuenta' => $data['titular_cuenta'] ?? null,
                'numero_cuenta' => $data['numero_cuenta'] ?? null,
                'nombre_banco' => $data['nombre_banco'] ?? null,
                'clabe_interbancaria' => $data['clabe_interbancaria'] ?? null,
                'sera_representado' => $data['sera_representado'] ?? null,
                'tipo_representacion' => $data['tipo_representacion'] ?? null,
                // Campos para Persona Moral
                'razon_social' => $data['razon_social'] ?? null,
                'dominio_internet' => $data['dominio_internet'] ?? null,
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
                'apoderado_curp' => $data['apoderado_curp'] ?? null,
                'apoderado_email' => $data['apoderado_email'] ?? null,
                'apoderado_telefono' => $data['apoderado_telefono'] ?? null,
                'apoderado_calle' => $data['apoderado_calle'] ?? null,
                'apoderado_numero_exterior' => $data['apoderado_numero_exterior'] ?? null,
                'apoderado_numero_interior' => $data['apoderado_numero_interior'] ?? null,
                'apoderado_cp' => $data['apoderado_cp'] ?? null,
                'apoderado_colonia' => $data['apoderado_colonia'] ?? null,
                'apoderado_municipio' => $data['apoderado_municipio'] ?? null,
                'apoderado_estado' => $data['apoderado_estado'] ?? null,
                'facultades_en_acta' => $data['facultades_en_acta'] ?? false,
                'escritura_publica_numero' => $data['escritura_publica_numero'] ?? null,
                'notario_numero_facultades' => $data['notario_numero_facultades'] ?? null,
                'fecha_escritura_facultades' => $data['fecha_escritura_facultades'] ?? null,
                'numero_inscripcion_registro_publico' => $data['numero_inscripcion_registro_publico'] ?? null,
                'ciudad_registro_facultades' => $data['ciudad_registro_facultades'] ?? null,
                'estado_registro_facultades' => $data['estado_registro_facultades'] ?? null,
                'tipo_representacion_moral' => $data['tipo_representacion_moral'] ?? null,
            ];
            
            $this->record->owner->update($ownerData);
        }

        return $data;
    }
}