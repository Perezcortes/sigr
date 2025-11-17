<?php

namespace App\Filament\Resources\RentResource\Pages;

use App\Filament\Resources\RentResource;
use App\Models\Rent;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Forms;
use Filament\Forms\Form;

class ViewRent extends ViewRecord
{
    protected static string $resource = RentResource::class;

    /**
     * Resuelve el record desde el parámetro de la ruta usando hash
     */
    public function resolveRecord(string|int $key): \Illuminate\Database\Eloquent\Model
    {
        // Intentar encontrar por hash primero
        $record = Rent::findByHash($key);
        
        // Si no se encuentra por hash, intentar por ID (compatibilidad hacia atrás)
        if (!$record && is_numeric($key)) {
            $record = Rent::find($key);
        }

        if (!$record) {
            abort(404);
        }

        return $record;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Tabs')
                    ->columnSpanFull()
                    ->tabs([
                        // TAB: INFORMACIÓN
                        Forms\Components\Tabs\Tab::make('Información')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                // SECCIÓN: DATOS GENERALES
                                Forms\Components\Section::make('Datos generales')
                                    ->schema([
                                        Forms\Components\TextInput::make('folio')
                                            ->label('Folio')
                                            ->default('RPG-3681-2025'),
                                        
                                        Forms\Components\TextInput::make('sucursal')
                                            ->label('Sucursal')
                                            ->default('Póliza de Rentas R&PG Consultores'),
                                        
                                        Forms\Components\TextInput::make('abogado')
                                            ->label('Abogado*')
                                            ->default('Ana Virinia Pérez Guemes y Ocampo'),
                                        
                                        Forms\Components\TextInput::make('inmobiliaria')
                                            ->label('Inmobiliaria*')
                                            ->default('Selecciona una inmobiliaria'),
                                        
                                        Forms\Components\Select::make('estatus')
                                            ->label('Estatus')
                                            ->options([
                                                'nueva' => 'Nueva',
                                                'en_proceso' => 'En Proceso',
                                                'completada' => 'Completada',
                                            ])
                                            ->default('nueva'),
                                        
                                        Forms\Components\TextInput::make('tipo_inmueble')
                                            ->label('Tipo de inmueble*')
                                            ->default('Inmuebles Residenciales'),
                                        
                                        Forms\Components\TextInput::make('tipo_poliza')
                                            ->label('Tipo de póliza')
                                            ->default('PÓLIZA CON SEGURO'),
                                        
                                        Forms\Components\TextInput::make('renta')
                                            ->label('Renta')
                                            ->numeric()
                                            ->prefix('$')
                                            ->default(13500),
                                        
                                        Forms\Components\TextInput::make('poliza')
                                            ->label('Póliza')
                                            ->numeric()
                                            ->prefix('$')
                                            ->default(0),
                                    ])
                                    ->columns(2),
                                
                                Forms\Components\Actions::make([
                                    Forms\Components\Actions\Action::make('guardar_general')
                                        ->label('Guardar')
                                        ->color('primary')
                                        ->icon('heroicon-o-check')
                                        ->action(function ($record, array $data) {
                                            $record->update($data);
                                            \Filament\Notifications\Notification::make()
                                                ->success()
                                                ->title('Guardado exitosamente')
                                                ->send();
                                        }),
                                    Forms\Components\Actions\Action::make('cancelar')
                                        ->label('Cancelar')
                                        ->color('gray')
                                        ->icon('heroicon-o-x-mark'),
                                    Forms\Components\Actions\Action::make('clonar_renta')
                                        ->label('Clonar renta')
                                        ->color('success')
                                        ->icon('heroicon-o-document-duplicate'),
                                ]),

                                // SECCIÓN: INQUILINO
                                Forms\Components\Section::make('Inquilino')
                                    ->schema([
                                        Forms\Components\Placeholder::make('tenant_info')
                                            ->label('Información del Inquilino')
                                            ->content(fn ($record) => $record->tenant ? $record->tenant->nombre_completo : 'Sin inquilino asignado'),
                                        
                                        Forms\Components\Radio::make('tenant_tipo_persona')
                                            ->label('Tipo de Persona')
                                            ->options([
                                                'fisica' => 'Persona física',
                                                'moral' => 'Persona moral',
                                            ])
                                            ->default(fn ($record) => $record->tenant?->tipo_persona ?? 'fisica')
                                            ->live()
                                            ->afterStateUpdated(function ($state, $record) {
                                                if ($record && $record->tenant) {
                                                    $record->tenant->update(['tipo_persona' => $state]);
                                                }
                                            }),
                                        
                                        Forms\Components\TextInput::make('tenant_nombres')
                                            ->label('Nombre:')
                                            ->default(fn ($record) => $record->tenant?->nombres)
                                            ->visible(fn (Forms\Get $get) => $get('tenant_tipo_persona') === 'fisica')
                                            ->afterStateUpdated(function ($state, $record) {
                                                if ($record && $record->tenant) {
                                                    $record->tenant->update(['nombres' => $state]);
                                                }
                                            }),
                                        
                                        Forms\Components\TextInput::make('tenant_primer_apellido')
                                            ->label('Primer Apellido:')
                                            ->default(fn ($record) => $record->tenant?->primer_apellido)
                                            ->visible(fn (Forms\Get $get) => $get('tenant_tipo_persona') === 'fisica')
                                            ->afterStateUpdated(function ($state, $record) {
                                                if ($record && $record->tenant) {
                                                    $record->tenant->update(['primer_apellido' => $state]);
                                                }
                                            }),
                                        
                                        Forms\Components\TextInput::make('tenant_segundo_apellido')
                                            ->label('Segundo Apellido:')
                                            ->default(fn ($record) => $record->tenant?->segundo_apellido)
                                            ->visible(fn (Forms\Get $get) => $get('tenant_tipo_persona') === 'fisica')
                                            ->afterStateUpdated(function ($state, $record) {
                                                if ($record && $record->tenant) {
                                                    $record->tenant->update(['segundo_apellido' => $state]);
                                                }
                                            }),
                                        
                                        Forms\Components\Select::make('tenant_sexo')
                                            ->label('Sexo:')
                                            ->options([
                                                'masculino' => 'Masculino',
                                                'femenino' => 'Femenino',
                                                'otro' => 'Otro',
                                            ])
                                            ->default(fn ($record) => $record->tenant?->sexo ?? 'Seleccione')
                                            ->afterStateUpdated(function ($state, $record) {
                                                if ($record && $record->tenant) {
                                                    $record->tenant->update(['sexo' => $state]);
                                                }
                                            }),
                                        
                                        Forms\Components\TextInput::make('tenant_email')
                                            ->label('Correo:')
                                            ->email()
                                            ->default(fn ($record) => $record->tenant?->email)
                                            ->afterStateUpdated(function ($state, $record) {
                                                if ($record && $record->tenant) {
                                                    $record->tenant->update(['email' => $state]);
                                                }
                                            }),
                                    ])
                                    ->columns(2),
                                
                                Forms\Components\Actions::make([
                                    Forms\Components\Actions\Action::make('edit_tenant')
                                        ->label('Editar solicitud del inquilino')
                                        ->color('primary'),
                                    Forms\Components\Actions\Action::make('send_tenant')
                                        ->label('Enviar solicitud al inquilino')
                                        ->color('success'),
                                    Forms\Components\Actions\Action::make('copy_link_tenant')
                                        ->label('Copiar link')
                                        ->color('gray'),
                                    Forms\Components\Actions\Action::make('export_pdf_tenant')
                                        ->label('Exportar PDF')
                                        ->color('warning'),
                                ]),

                                // SECCIÓN: FIADOR
                                Forms\Components\Section::make('Fiador')
                                    ->schema([
                                        Forms\Components\Select::make('tiene_fiador')
                                            ->label('¿Tiene fiador?')
                                            ->options([
                                                'si' => 'Sí',
                                                'no' => 'No',
                                            ])
                                            ->default('no'),
                                    ]),
                                
                                Forms\Components\Actions::make([
                                    Forms\Components\Actions\Action::make('edit_guarantor')
                                        ->label('Editar solicitud del fiador')
                                        ->color('primary'),
                                    Forms\Components\Actions\Action::make('send_guarantor')
                                        ->label('Enviar solicitud al fiador')
                                        ->color('success'),
                                    Forms\Components\Actions\Action::make('copy_link_guarantor')
                                        ->label('Copiar link')
                                        ->color('gray'),
                                ]),

                                // SECCIÓN: PROPIETARIO
                                Forms\Components\Section::make('Propietario')
                                    ->schema([
                                        Forms\Components\Placeholder::make('owner_info')
                                            ->label('Información del Propietario')
                                            ->content(fn ($record) => $record->owner ? $record->owner->nombre_completo : 'Sin propietario asignado'),
                                        
                                        Forms\Components\Radio::make('owner_tipo_persona')
                                            ->label('Tipo de Persona')
                                            ->options([
                                                'fisica' => 'Persona física',
                                                'moral' => 'Persona moral',
                                            ])
                                            ->default(fn ($record) => $record->owner?->tipo_persona ?? 'fisica')
                                            ->live()
                                            ->afterStateUpdated(function ($state, $record) {
                                                if ($record && $record->owner) {
                                                    $record->owner->update(['tipo_persona' => $state]);
                                                }
                                            }),
                                        
                                        Forms\Components\TextInput::make('owner_nombres')
                                            ->label('Nombre:')
                                            ->default(fn ($record) => $record->owner?->nombres)
                                            ->visible(fn (Forms\Get $get) => $get('owner_tipo_persona') === 'fisica')
                                            ->afterStateUpdated(function ($state, $record) {
                                                if ($record && $record->owner) {
                                                    $record->owner->update(['nombres' => $state]);
                                                }
                                            }),
                                        
                                        Forms\Components\TextInput::make('owner_primer_apellido')
                                            ->label('Primer Apellido:')
                                            ->default(fn ($record) => $record->owner?->primer_apellido)
                                            ->visible(fn (Forms\Get $get) => $get('owner_tipo_persona') === 'fisica')
                                            ->afterStateUpdated(function ($state, $record) {
                                                if ($record && $record->owner) {
                                                    $record->owner->update(['primer_apellido' => $state]);
                                                }
                                            }),
                                        
                                        Forms\Components\TextInput::make('owner_segundo_apellido')
                                            ->label('Segundo Apellido:')
                                            ->default(fn ($record) => $record->owner?->segundo_apellido)
                                            ->visible(fn (Forms\Get $get) => $get('owner_tipo_persona') === 'fisica')
                                            ->afterStateUpdated(function ($state, $record) {
                                                if ($record && $record->owner) {
                                                    $record->owner->update(['segundo_apellido' => $state]);
                                                }
                                            }),
                                        
                                        Forms\Components\Select::make('owner_sexo')
                                            ->label('Sexo:')
                                            ->options([
                                                'masculino' => 'Masculino',
                                                'femenino' => 'Femenino',
                                                'otro' => 'Otro',
                                            ])
                                            ->default(fn ($record) => $record->owner?->sexo ?? 'Seleccione')
                                            ->afterStateUpdated(function ($state, $record) {
                                                if ($record && $record->owner) {
                                                    $record->owner->update(['sexo' => $state]);
                                                }
                                            }),
                                        
                                        Forms\Components\TextInput::make('owner_email')
                                            ->label('Correo:')
                                            ->email()
                                            ->default(fn ($record) => $record->owner?->email)
                                            ->afterStateUpdated(function ($state, $record) {
                                                if ($record && $record->owner) {
                                                    $record->owner->update(['email' => $state]);
                                                }
                                            }),
                                    ])
                                    ->columns(2),
                                
                                Forms\Components\Actions::make([
                                    Forms\Components\Actions\Action::make('edit_owner')
                                        ->label('Editar solicitud del propietario')
                                        ->color('primary'),
                                    Forms\Components\Actions\Action::make('send_owner')
                                        ->label('Enviar solicitud al propietario')
                                        ->color('success'),
                                    Forms\Components\Actions\Action::make('copy_link_owner')
                                        ->label('Copiar link')
                                        ->color('gray'),
                                    Forms\Components\Actions\Action::make('export_pdf_owner')
                                        ->label('Exportar PDF')
                                        ->color('warning'),
                                ]),

                                // SECCIÓN: PROPIEDAD
                                Forms\Components\Section::make('Propiedad')
                                    ->schema([
                                        Forms\Components\TextInput::make('tipo_propiedad')
                                            ->label('Tipo de Propiedad')
                                            ->default('Seleccione'),
                                        
                                        Forms\Components\TextInput::make('calle')
                                            ->label('Calle')
                                            ->default(''),
                                        
                                        Forms\Components\TextInput::make('numero_exterior')
                                            ->label('Núm Ext')
                                            ->default(''),
                                        
                                        Forms\Components\TextInput::make('numero_interior')
                                            ->label('Núm Int')
                                            ->default(''),
                                        
                                        Forms\Components\TextInput::make('referencias_ubicacion')
                                            ->label('Referencias Ubicación')
                                            ->default(''),
                                        
                                        Forms\Components\TextInput::make('colonia')
                                            ->label('Colonia')
                                            ->default(''),
                                        
                                        Forms\Components\TextInput::make('municipio')
                                            ->label('Municipio/Alcaldía')
                                            ->default(''),
                                        
                                        Forms\Components\TextInput::make('estado')
                                            ->label('Estado')
                                            ->default('Seleccione'),
                                        
                                        Forms\Components\TextInput::make('codigo_postal')
                                            ->label('CP')
                                            ->default(''),
                                    ])
                                    ->columns(2),
                                
                                Forms\Components\Actions::make([
                                    Forms\Components\Actions\Action::make('guardar_propiedad')
                                        ->label('Guardar')
                                        ->color('primary')
                                        ->icon('heroicon-o-check')
                                        ->action(function ($record, array $data) {
                                            $record->update($data);
                                            \Filament\Notifications\Notification::make()
                                                ->success()
                                                ->title('Guardado exitosamente')
                                                ->send();
                                        }),
                                ]),
                            ]),

                        // TAB: DOCUMENTOS
                        Forms\Components\Tabs\Tab::make('Documentos')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Forms\Components\Section::make('Documentos')
                                    ->schema([
                                        Forms\Components\Textarea::make('documentos_info')
                                            ->label('')
                                            ->default('Aquí se mostrarán los documentos relacionados con la renta.')
                                            ->placeholder('No hay documentos disponibles')
                                            ->rows(3),
                                    ]),
                                
                                Forms\Components\Actions::make([
                                    Forms\Components\Actions\Action::make('subir_documento')
                                        ->label('Subir Documento')
                                        ->color('primary')
                                        ->icon('heroicon-o-arrow-up-tray'),
                                ]),
                            ]),

                        // TAB: INVESTIGACIÓN
                        Forms\Components\Tabs\Tab::make('Investigación')
                            ->icon('heroicon-o-magnifying-glass')
                            ->schema([
                                Forms\Components\Section::make('Investigación')
                                    ->schema([
                                        Forms\Components\Textarea::make('investigacion_info')
                                            ->label('')
                                            ->default('Aquí se mostrará la información de investigación relacionada con la renta.')
                                            ->placeholder('No hay información de investigación disponible')
                                            ->rows(3),
                                    ]),
                                
                                Forms\Components\Actions::make([
                                    Forms\Components\Actions\Action::make('nueva_investigacion')
                                        ->label('Nueva Investigación')
                                        ->color('primary')
                                        ->icon('heroicon-o-plus-circle'),
                                ]),
                            ]),
                    ]),
            ]);
    }

}