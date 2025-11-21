<?php

namespace App\Filament\Resources\RentResource\Pages;

use App\Filament\Resources\RentResource;
use App\Models\Rent;
use App\Models\TenantRequest;
use App\Models\OwnerRequest;
use App\Filament\Resources\TenantRequestResource;
use App\Filament\Resources\OwnerRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms;
use Filament\Forms\Form;

class ViewRent extends EditRecord
{
    protected static string $resource = RentResource::class;
    protected static ?string $title = 'Ver Renta';

    // Estado personalizado para manejar los campos de relaciones
    public ?array $tenantData = [];
    public ?array $ownerData = [];

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

    // Inicializar el estado personalizado cuando se carga el registro
    public function mount($record): void
    {
        parent::mount($record);

        // Inicializar datos del inquilino
        if ($this->record->tenant) {
            $this->tenantData = [
                'tipo_persona' => $this->record->tenant->tipo_persona,
                'nombres' => $this->record->tenant->nombres,
                'primer_apellido' => $this->record->tenant->primer_apellido,
                'segundo_apellido' => $this->record->tenant->segundo_apellido,
                'sexo' => $this->record->tenant->sexo,
                'razon_social' => $this->record->tenant->razon_social,
                'rfc' => $this->record->tenant->rfc,
                'email' => $this->record->tenant->email,
            ];
            
            // IMPORTANTE: Establecer también en el data del formulario
            $this->data['tenant_tipo_persona'] = $this->record->tenant->tipo_persona;
            $this->data['tenant_nombres'] = $this->record->tenant->nombres;
            $this->data['tenant_primer_apellido'] = $this->record->tenant->primer_apellido;
            $this->data['tenant_segundo_apellido'] = $this->record->tenant->segundo_apellido;
            $this->data['tenant_sexo'] = $this->record->tenant->sexo;
            $this->data['tenant_razon_social'] = $this->record->tenant->razon_social;
            $this->data['tenant_rfc'] = $this->record->tenant->rfc;
            $this->data['tenant_email'] = $this->record->tenant->email;
        }

        // Inicializar datos del propietario
        if ($this->record->owner) {
            $this->ownerData = [
                'tipo_persona' => $this->record->owner->tipo_persona,
                'nombres' => $this->record->owner->nombres,
                'primer_apellido' => $this->record->owner->primer_apellido,
                'segundo_apellido' => $this->record->owner->segundo_apellido,
                'sexo' => $this->record->owner->sexo,
                'razon_social' => $this->record->owner->razon_social,
                'rfc' => $this->record->owner->rfc,
                'email' => $this->record->owner->email,
            ];
            
            // IMPORTANTE: Establecer también en el data del formulario
            $this->data['owner_tipo_persona'] = $this->record->owner->tipo_persona;
            $this->data['owner_nombres'] = $this->record->owner->nombres;
            $this->data['owner_primer_apellido'] = $this->record->owner->primer_apellido;
            $this->data['owner_segundo_apellido'] = $this->record->owner->segundo_apellido;
            $this->data['owner_sexo'] = $this->record->owner->sexo;
            $this->data['owner_razon_social'] = $this->record->owner->razon_social;
            $this->data['owner_rfc'] = $this->record->owner->rfc;
            $this->data['owner_email'] = $this->record->owner->email;
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('volver')
                ->label('Volver al listado')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn () => RentResource::getUrl('index')),
        ];
    }

    protected function getFormActions(): array
    {
        return [];
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
                                Forms\Components\Section::make('Datos de la renta')
                                    ->schema([
                                        Forms\Components\TextInput::make('folio')
                                            ->label('Folio')
                                            ->disabled(),
                                        Forms\Components\TextInput::make('sucursal')
                                            ->label('Sucursal')
                                            ->disabled(),
                                        Forms\Components\TextInput::make('abogado')
                                            ->label('Abogado*')
                                            ->disabled(),
                                        Forms\Components\TextInput::make('inmobiliaria')
                                            ->label('Inmobiliaria*')
                                            ->disabled(),
                                        Forms\Components\Select::make('estatus')
                                            ->label('Estatus')
                                            ->options([
                                                'nueva' => 'Nueva',
                                                'documentacion' => 'Documentación',
                                                'analisis' => 'Análisis',
                                            ])
                                            ->default('nueva')
                                            ->required(),
                                        Forms\Components\Select::make('tipo_inmueble')
                                            ->label('Tipo de inmueble*')
                                            ->options([
                                                'residencial' => 'Inmuebles Residenciales',
                                                'comercial' => 'Inmuebles Comerciales',
                                            ])
                                            ->default('residencial')
                                            ->required(),
                                        Forms\Components\Select::make('tipo_poliza')
                                            ->label('Tipo de póliza')
                                            ->options([
                                                'integral' => 'Póliza Integral',
                                                'amplia' => 'Póliza Amplia',
                                                'con_seguro' => 'Póliza con Seguro',
                                            ])
                                            ->default('con_seguro')
                                            ->required(),
                                        Forms\Components\TextInput::make('renta')
                                            ->label('Renta')
                                            ->numeric()
                                            ->prefix('$')
                                            ->required()
                                            ->placeholder('0.00'),
                                        Forms\Components\TextInput::make('poliza')
                                            ->label('Póliza')
                                            ->numeric()
                                            ->prefix('$')
                                            ->required()
                                            ->placeholder('0.00'),
                                    ])
                                    ->columns(2),
                                
                                Forms\Components\Actions::make([
                                    Forms\Components\Actions\Action::make('guardar_general')
                                        ->label('Guardar')
                                        ->color('primary')
                                        ->icon('heroicon-o-check')
                                        ->action(function () {
                                            $this->save();
                                            \Filament\Notifications\Notification::make()
                                                ->success()
                                                ->title('Datos guardados')
                                                ->send();
                                        }),
                                    Forms\Components\Actions\Action::make('cancelar')
                                        ->label('Cancelar')
                                        ->color('gray')
                                        ->icon('heroicon-o-x-mark')
                                        ->url(fn () => RentResource::getUrl('index')),
                                    Forms\Components\Actions\Action::make('clonar_renta')
                                        ->label('Clonar renta')
                                        ->color('success')
                                        ->icon('heroicon-o-document-duplicate'),
                                ]),

                                // SECCIÓN: INQUILINO
                                Forms\Components\Section::make('Datos del inquilino')
                                    ->schema([
                                        // Mostrar información actual del inquilino
                                        Forms\Components\Placeholder::make('current_tenant_info')
                                            ->label('Información actual del inquilino')
                                            ->content(function () {
                                                $tenant = $this->record->tenant;
                                                if (!$tenant) {
                                                    return 'No hay inquilino asignado';
                                                }
                                                
                                                if ($tenant->tipo_persona === 'fisica') {
                                                    return "Tipo: Persona Física\nNombre: {$tenant->nombres} {$tenant->primer_apellido} {$tenant->segundo_apellido}\nEmail: {$tenant->email}";
                                                } else {
                                                    return "Tipo: Persona Moral\nRazón Social: {$tenant->razon_social}\nEmail: {$tenant->email}\nRFC: {$tenant->rfc}";
                                                }
                                            })
                                            ->columnSpanFull(),
                                        
                                        Forms\Components\Select::make('tenant_tipo_persona')
                                            ->label('Tipo de Persona')
                                            ->options([
                                                'fisica' => 'Persona física',
                                                'moral' => 'Persona moral',
                                            ])
                                            ->live()
                                            ->required()
                                            ->columnSpanFull(),
                                        
                                        // Campos para Persona Física
                                        Forms\Components\TextInput::make('tenant_nombres')
                                            ->label('Nombre')
                                            ->visible(fn (Forms\Get $get) => $get('tenant_tipo_persona') === 'fisica'),
                                        
                                        Forms\Components\TextInput::make('tenant_primer_apellido')
                                            ->label('Primer Apellido')
                                            ->visible(fn (Forms\Get $get) => $get('tenant_tipo_persona') === 'fisica'),
                                        
                                        Forms\Components\TextInput::make('tenant_segundo_apellido')
                                            ->label('Segundo Apellido')
                                            ->visible(fn (Forms\Get $get) => $get('tenant_tipo_persona') === 'fisica'),

                                        Forms\Components\Select::make('tenant_sexo')
                                            ->label('Sexo')
                                            ->options([
                                                'masculino' => 'Masculino',
                                                'femenino' => 'Femenino',
                                                'otro' => 'Otro',
                                            ])
                                            ->placeholder('Seleccione')
                                            ->visible(fn (Forms\Get $get) => $get('tenant_tipo_persona') === 'fisica'),

                                        // Campos para Persona Moral
                                        Forms\Components\TextInput::make('tenant_razon_social')
                                            ->label('Nombre / Razón Social')
                                            ->visible(fn (Forms\Get $get) => $get('tenant_tipo_persona') === 'moral')
                                            ->columnSpan(2),

                                        Forms\Components\TextInput::make('tenant_rfc')
                                            ->label('RFC')
                                            ->visible(fn (Forms\Get $get) => $get('tenant_tipo_persona') === 'moral'),

                                        // Campo común para ambos tipos
                                        Forms\Components\TextInput::make('tenant_email')
                                            ->label('Correo')
                                            ->email(),
                                    ])
                                    ->columns(4),
                                
                                Forms\Components\Actions::make([
                                    Forms\Components\Actions\Action::make('actualizar_inquilino')
                                        ->label('Guardar')
                                        ->color('primary')
                                        ->icon('heroicon-o-check')
                                        ->action(function () {
                                            if ($this->record->tenant) {
                                                $updateData = [
                                                    'tipo_persona' => $this->data['tenant_tipo_persona'] ?? 'fisica',
                                                    'email' => $this->data['tenant_email'] ?? '',
                                                ];
                                                
                                                if ($this->data['tenant_tipo_persona'] === 'fisica') {
                                                    $updateData['nombres'] = $this->data['tenant_nombres'] ?? '';
                                                    $updateData['primer_apellido'] = $this->data['tenant_primer_apellido'] ?? '';
                                                    $updateData['segundo_apellido'] = $this->data['tenant_segundo_apellido'] ?? '';
                                                    $updateData['sexo'] = $this->data['tenant_sexo'] ?? '';
                                                    $updateData['razon_social'] = null;
                                                    $updateData['rfc'] = null;
                                                } else {
                                                    $updateData['razon_social'] = $this->data['tenant_razon_social'] ?? '';
                                                    $updateData['rfc'] = $this->data['tenant_rfc'] ?? '';
                                                    $updateData['nombres'] = null;
                                                    $updateData['primer_apellido'] = null;
                                                    $updateData['segundo_apellido'] = null;
                                                    $updateData['sexo'] = null;
                                                }
                                                
                                                $this->record->tenant->update($updateData);
                                                
                                                \Filament\Notifications\Notification::make()
                                                    ->success()
                                                    ->title('Inquilino actualizado')
                                                    ->send();
                                                
                                                // Recargar la página para mostrar los cambios
                                                $this->redirect(RentResource::getUrl('view', ['record' => $this->record]));
                                            }
                                        }),
                                    Forms\Components\Actions\Action::make('edit_tenant')
                                        ->label('Editar solicitud del inquilino')
                                        ->color('primary')
                                        ->action(function () {
                                            // Buscar si ya existe una solicitud para este inquilino y renta
                                            $tenantRequest = TenantRequest::where('tenant_id', $this->record->tenant_id)
                                                ->where('rent_id', $this->record->id)
                                                ->first();
                                            
                                            if (!$tenantRequest) {
                                                // Crear nueva solicitud si no existe
                                                $tenantRequest = TenantRequest::create([
                                                    'tenant_id' => $this->record->tenant_id,
                                                    'rent_id' => $this->record->id,
                                                    'estatus' => 'nueva',
                                                    // Pre-cargar datos básicos del inquilino
                                                    'nombres' => $this->record->tenant->nombres,
                                                    'primer_apellido' => $this->record->tenant->primer_apellido,
                                                    'segundo_apellido' => $this->record->tenant->segundo_apellido,
                                                    'email' => $this->record->tenant->email,
                                                    'rfc' => $this->record->tenant->rfc,
                                                    'telefono_celular' => $this->record->tenant->telefono_celular,
                                                    'telefono_fijo' => $this->record->tenant->telefono_fijo,
                                                    'sexo' => $this->record->tenant->sexo,
                                                    'estado_civil' => $this->record->tenant->estado_civil,
                                                    'nacionalidad' => $this->record->tenant->nacionalidad,
                                                    'tipo_identificacion' => $this->record->tenant->tipo_identificacion,
                                                ]);
                                            }
                                            
                                            $this->redirect(TenantRequestResource::getUrl('edit', ['record' => $tenantRequest]));
                                        })
                                        ->visible(fn () => $this->record->tenant),
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
                                Forms\Components\Section::make('Datos del Obligado solidario / Fiador')
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
                                    Forms\Components\Actions\Action::make('actualizar_fiador')
                                        ->label('Guardar')
                                        ->color('primary')
                                        ->icon('heroicon-o-check')
                                        ->action(function () {
                                            $this->save();
                                            \Filament\Notifications\Notification::make()
                                                ->success()
                                                ->title('Datos del fiador guardados')
                                                ->send();
                                        }),
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
                                Forms\Components\Section::make('Datos del propietario')
                                    ->schema([
                                        // Mostrar información actual del propietario
                                        Forms\Components\Placeholder::make('current_owner_info')
                                            ->label('Información actual del propietario')
                                            ->content(function () {
                                                $owner = $this->record->owner;
                                                if (!$owner) {
                                                    return 'No hay propietario asignado';
                                                }
                                                
                                                if ($owner->tipo_persona === 'fisica') {
                                                    return "Tipo: Persona Física\nNombre: {$owner->nombres} {$owner->primer_apellido} {$owner->segundo_apellido}\nEmail: {$owner->email}";
                                                } else {
                                                    return "Tipo: Persona Moral\nRazón Social: {$owner->razon_social}\nEmail: {$owner->email}\nRFC: {$owner->rfc}";
                                                }
                                            })
                                            ->columnSpanFull(),
                                        
                                        Forms\Components\Select::make('owner_tipo_persona')
                                            ->label('Tipo de Persona')
                                            ->options([
                                                'fisica' => 'Persona física',
                                                'moral' => 'Persona moral',
                                            ])
                                            ->live()
                                            ->required()
                                            ->columnSpanFull(),
                                        
                                        // Campos para Persona Física
                                        Forms\Components\TextInput::make('owner_nombres')
                                            ->label('Nombre')
                                            ->visible(fn (Forms\Get $get) => $get('owner_tipo_persona') === 'fisica'),
                                        
                                        Forms\Components\TextInput::make('owner_primer_apellido')
                                            ->label('Primer Apellido')
                                            ->visible(fn (Forms\Get $get) => $get('owner_tipo_persona') === 'fisica'),
                                        
                                        Forms\Components\TextInput::make('owner_segundo_apellido')
                                            ->label('Segundo Apellido')
                                            ->visible(fn (Forms\Get $get) => $get('owner_tipo_persona') === 'fisica'),

                                        Forms\Components\Select::make('owner_sexo')
                                            ->label('Sexo')
                                            ->options([
                                                'masculino' => 'Masculino',
                                                'femenino' => 'Femenino',
                                                'otro' => 'Otro',
                                            ])
                                            ->placeholder('Seleccione')
                                            ->visible(fn (Forms\Get $get) => $get('owner_tipo_persona') === 'fisica'),

                                        // Campos para Persona Moral
                                        Forms\Components\TextInput::make('owner_razon_social')
                                            ->label('Nombre / Razón Social')
                                            ->visible(fn (Forms\Get $get) => $get('owner_tipo_persona') === 'moral')
                                            ->columnSpan(2),

                                        Forms\Components\TextInput::make('owner_rfc')
                                            ->label('RFC')
                                            ->visible(fn (Forms\Get $get) => $get('owner_tipo_persona') === 'moral'),

                                        // Campo común para ambos tipos
                                        Forms\Components\TextInput::make('owner_email')
                                            ->label('Correo')
                                            ->email(),
                                    ])
                                    ->columns(4),
                                
                                Forms\Components\Actions::make([
                                    Forms\Components\Actions\Action::make('actualizar_propietario')
                                        ->label('Guardar')
                                        ->color('primary')
                                        ->icon('heroicon-o-check')
                                        ->action(function () {
                                            if ($this->record->owner) {
                                                $updateData = [
                                                    'tipo_persona' => $this->data['owner_tipo_persona'] ?? 'fisica',
                                                    'email' => $this->data['owner_email'] ?? '',
                                                ];
                                                
                                                if ($this->data['owner_tipo_persona'] === 'fisica') {
                                                    $updateData['nombres'] = $this->data['owner_nombres'] ?? '';
                                                    $updateData['primer_apellido'] = $this->data['owner_primer_apellido'] ?? '';
                                                    $updateData['segundo_apellido'] = $this->data['owner_segundo_apellido'] ?? '';
                                                    $updateData['sexo'] = $this->data['owner_sexo'] ?? '';
                                                    $updateData['razon_social'] = null;
                                                    $updateData['rfc'] = null;
                                                } else {
                                                    $updateData['razon_social'] = $this->data['owner_razon_social'] ?? '';
                                                    $updateData['rfc'] = $this->data['owner_rfc'] ?? '';
                                                    $updateData['nombres'] = null;
                                                    $updateData['primer_apellido'] = null;
                                                    $updateData['segundo_apellido'] = null;
                                                    $updateData['sexo'] = null;
                                                }
                                                
                                                $this->record->owner->update($updateData);
                                                
                                                \Filament\Notifications\Notification::make()
                                                    ->success()
                                                    ->title('Propietario actualizado')
                                                    ->send();
                                                
                                                // Recargar la página para mostrar los cambios
                                                $this->redirect(RentResource::getUrl('view', ['record' => $this->record]));
                                            }
                                        }),
                                    Forms\Components\Actions\Action::make('edit_owner')
                                        ->label('Editar solicitud del propietario')
                                        ->color('primary')
                                        ->action(function () {
                                            // Buscar si ya existe una solicitud para este propietario y renta
                                            $ownerRequest = OwnerRequest::where('owner_id', $this->record->owner_id)
                                                ->where('rent_id', $this->record->id)
                                                ->first();
                                            
                                            if (!$ownerRequest) {
                                                // Crear nueva solicitud si no existe
                                                $ownerRequest = OwnerRequest::create([
                                                    'owner_id' => $this->record->owner_id,
                                                    'rent_id' => $this->record->id,
                                                    'estatus' => 'nueva',
                                                    // Pre-cargar datos básicos del propietario
                                                    'nombres' => $this->record->owner->nombres,
                                                    'primer_apellido' => $this->record->owner->primer_apellido,
                                                    'segundo_apellido' => $this->record->owner->segundo_apellido,
                                                    'email' => $this->record->owner->email,
                                                    'rfc' => $this->record->owner->rfc,
                                                    'telefono' => $this->record->owner->telefono,
                                                    'sexo' => $this->record->owner->sexo,
                                                    'estado_civil' => $this->record->owner->estado_civil,
                                                    'nacionalidad' => $this->record->owner->nacionalidad,
                                                    'tipo_identificacion' => $this->record->owner->tipo_identificacion,
                                                ]);
                                            }
                                            
                                            $this->redirect(OwnerRequestResource::getUrl('edit', ['record' => $ownerRequest]));
                                        })
                                        ->visible(fn () => $this->record->owner),
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
                                Forms\Components\Section::make('Datos de la propiedad')
                                    ->schema([
                                        Forms\Components\Select::make('tipo_propiedad')
                                            ->label('Tipo de Propiedad')
                                            ->options([
                                                'seleccione' => 'Seleccione',
                                                'casa' => 'Casa',
                                                'departamento' => 'Departamento',
                                                'local_comercial' => 'Local comercial',
                                                'oficina' => 'Oficina',
                                                'bodega' => 'Bodega',
                                                'nave_industrial' => 'Nave Industrial',
                                                'consultorio' => 'Consultorio',
                                                'terreno' => 'Terreno',
                                            ])
                                            ->placeholder('Seleccione'),
                                        
                                        Forms\Components\TextInput::make('calle')
                                            ->label('Calle'),
                                        
                                        Forms\Components\TextInput::make('numero_exterior')
                                            ->label('Núm Ext'),
                                        
                                        Forms\Components\TextInput::make('numero_interior')
                                            ->label('Núm Int'),
                                        
                                        Forms\Components\Textarea::make('referencias_ubicacion')
                                            ->label('Referencias Ubicación')
                                            ->rows(2)
                                            ->columnSpanFull(),
                                        
                                        Forms\Components\TextInput::make('colonia')
                                            ->label('Colonia'),
                                        
                                        Forms\Components\TextInput::make('municipio')
                                            ->label('Municipio/Alcaldía'),
                                        
                                        Forms\Components\Select::make('estado')
                                            ->label('Estado')
                                            ->options([
                                                'aguascalientes' => 'Aguascalientes',
                                                'baja_california' => 'Baja California',
                                                'baja_california_sur' => 'Baja California Sur',
                                                'campeche' => 'Campeche',
                                                'chiapas' => 'Chiapas',
                                                'chihuahua' => 'Chihuahua',
                                                'cdmx' => 'Ciudad de México',
                                                'coahuila' => 'Coahuila',
                                                'colima' => 'Colima',
                                                'durango' => 'Durango',
                                                'guanajuato' => 'Guanajuato',
                                                'guerrero' => 'Guerrero',
                                                'hidalgo' => 'Hidalgo',
                                                'jalisco' => 'Jalisco',
                                                'mexico' => 'Estado de México',
                                                'michoacan' => 'Michoacán',
                                                'morelos' => 'Morelos',
                                                'nayarit' => 'Nayarit',
                                                'nuevo_leon' => 'Nuevo León',
                                                'oaxaca' => 'Oaxaca',
                                                'puebla' => 'Puebla',
                                                'queretaro' => 'Querétaro',
                                                'quintana_roo' => 'Quintana Roo',
                                                'san_luis_potosi' => 'San Luis Potosí',
                                                'sinaloa' => 'Sinaloa',
                                                'sonora' => 'Sonora',
                                                'tabasco' => 'Tabasco',
                                                'tamaulipas' => 'Tamaulipas',
                                                'tlaxcala' => 'Tlaxcala',
                                                'veracruz' => 'Veracruz',
                                                'yucatan' => 'Yucatán',
                                                'zacatecas' => 'Zacatecas',
                                            ])
                                            ->placeholder('Seleccione'),
                                        
                                        Forms\Components\TextInput::make('codigo_postal')
                                            ->label('CP')
                                            ->numeric()
                                            ->maxLength(5),
                                    ])
                                    ->columns(2),
                                
                                Forms\Components\Actions::make([
                                    Forms\Components\Actions\Action::make('guardar_propiedad')
                                        ->label('Guardar')
                                        ->color('primary')
                                        ->icon('heroicon-o-check')
                                        ->action(function () {
                                            $this->save();
                                            \Filament\Notifications\Notification::make()
                                                ->success()
                                                ->title('Datos de propiedad guardados')
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
                                        Forms\Components\Placeholder::make('documentos_placeholder')
                                            ->label('')
                                            ->content('Aquí se mostrarán los documentos relacionados con la renta.'),
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
                                        Forms\Components\Placeholder::make('investigacion_placeholder')
                                            ->label('')
                                            ->content('Aquí se mostrará la información de investigación relacionada con la renta.'),
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