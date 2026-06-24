<?php

namespace App\Filament\Resources\OwnerRequestResource\Pages;

use App\Filament\Resources\OwnerRequestResource;
use App\Models\Property;
use App\Models\PropertyImage;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOwnerRequest extends EditRecord
{
    protected static string $resource = OwnerRequestResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->record->loadMissing('rent');
        $data['selected_property_id'] = $this->record->rent?->property_id;

        return $data;
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
        $selectedPropertyId = $data['selected_property_id'] ?? null;
        unset($data['selected_property_id']);

        if ($this->record->rent) {
            $rentUpdate = ['property_id' => $selectedPropertyId ?: null];

            if ($selectedPropertyId) {
                $property = Property::query()->find($selectedPropertyId);
                if ($property) {
                    $rentUpdate = array_merge($rentUpdate, [
                        'tipo_propiedad' => $property->tipo_inmueble,
                        'calle' => $property->calle,
                        'numero_exterior' => $property->numero_exterior,
                        'numero_interior' => $property->numero_interior,
                        'codigo_postal' => $property->codigo_postal,
                        'colonia' => $property->colonia,
                        'municipio' => $property->delegacion_municipio,
                        'estado' => $property->estado,
                        'referencias_ubicacion' => $property->referencias_ubicacion,
                    ]);
                }
            }

            $this->record->rent->update($rentUpdate);
        }

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

    public function deleteOwnerRequestPropertyImage(int $id): void
    {
        $propertyId = $this->data['selected_property_id'] ?? null;
        if (! $propertyId) {
            return;
        }

        $image = PropertyImage::query()->find($id);
        if (! $image || (int) $image->property_id !== (int) $propertyId) {
            return;
        }

        if ($image->is_portada) {
            $otherImage = PropertyImage::query()
                ->where('property_id', $propertyId)
                ->where('id', '!=', $id)
                ->first();

            if ($otherImage) {
                $otherImage->update(['is_portada' => true]);
            }
        }

        $image->delete();

        \Filament\Notifications\Notification::make()
            ->success()
            ->title('Imagen eliminada')
            ->send();
    }

    public function setOwnerRequestPropertyPortada(int $id): void
    {
        $propertyId = $this->data['selected_property_id'] ?? null;
        if (! $propertyId) {
            return;
        }

        $property = Property::query()->find($propertyId);
        $image = PropertyImage::query()->find($id);
        if (! $property || ! $image || (int) $image->property_id !== (int) $property->id) {
            return;
        }

        PropertyImage::query()
            ->where('property_id', $property->id)
            ->update(['is_portada' => false]);

        $image->update(['is_portada' => true]);

        \Filament\Notifications\Notification::make()
            ->success()
            ->title('Imagen marcada como portada')
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return \App\Filament\Resources\RentResource::getUrl('view', [
            'record' => $this->record->rent->hash_id ?? $this->record->rent_id,
        ]) . '?tab=-solicitudes-tab&solicitud=-propietario-tab';
    }
}