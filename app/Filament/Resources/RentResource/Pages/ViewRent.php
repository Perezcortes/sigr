<?php

namespace App\Filament\Resources\RentResource\Pages;

use App\Filament\Resources\RentResource;
use App\Models\Rent;
use App\Models\TenantRequest;
use App\Models\OwnerRequest;
use App\Models\TenantDocument;
use App\Models\GuarantorDocument;
use App\Models\OwnerDocument;
use App\Models\PropertyDocument;
use App\Models\RentComment;
use App\Filament\Resources\TenantRequestResource;
use App\Filament\Resources\OwnerRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms;
use Filament\Forms\Form;
use Illuminate\Support\Facades\Storage;

class ViewRent extends EditRecord
{
    protected static string $resource = RentResource::class;
    protected static ?string $title = 'Ver Renta';
    protected static string $view = 'filament.resources.rent-resource.pages.view-rent';

    public ?array $tenantData = [];
    public ?array $ownerData = [];

    public function resolveRecord(string|int $key): \Illuminate\Database\Eloquent\Model
    {   
        $record = Rent::findByHash($key);
        if (!$record && is_numeric($key)) {
            $record = Rent::find($key);
        }
        if (!$record) {
            abort(404);
        }
        return $record;
    }

    public function mount($record): void
    {
        parent::mount($record);

        if ($this->record->tenant) {
            $this->data['tenant_tipo_persona'] = $this->record->tenant->tipo_persona;
            $this->data['tenant_nombres'] = $this->record->tenant->nombres;
            $this->data['tenant_primer_apellido'] = $this->record->tenant->primer_apellido;
            $this->data['tenant_segundo_apellido'] = $this->record->tenant->segundo_apellido;
            $this->data['tenant_sexo'] = $this->record->tenant->sexo;
            $this->data['tenant_razon_social'] = $this->record->tenant->razon_social;
            $this->data['tenant_rfc'] = $this->record->tenant->rfc;
            $this->data['tenant_email'] = $this->record->tenant->email;
        }

        if ($this->record->owner) {
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

    public function deleteTenantDocument(int $id): void
    {
        TenantDocument::find($id)?->delete();
        \Filament\Notifications\Notification::make()->success()->title('Documento eliminado')->send();
    }

    public function deleteGuarantorDocument(int $id): void
    {
        GuarantorDocument::find($id)?->delete();
        \Filament\Notifications\Notification::make()->success()->title('Documento eliminado')->send();
    }

    public function deleteOwnerDocument(int $id): void
    {
        OwnerDocument::find($id)?->delete();
        \Filament\Notifications\Notification::make()->success()->title('Documento eliminado')->send();
    }

    public function deletePropertyDocument(int $id): void
    {
        PropertyDocument::find($id)?->delete();
        \Filament\Notifications\Notification::make()->success()->title('Documento eliminado')->send();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Tabs')
                    ->columnSpanFull()
                    ->tabs([
                        // ========== TAB: INFORMACIÓN ==========
                        Forms\Components\Tabs\Tab::make('Información')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Forms\Components\Section::make('Datos de la renta')
                                    ->schema([
                                        Forms\Components\TextInput::make('folio')->label('Folio')->disabled(),
                                        Forms\Components\TextInput::make('sucursal')->label('Sucursal')->disabled(),
                                        Forms\Components\TextInput::make('abogado')->label('Abogado*')->disabled(),
                                        Forms\Components\TextInput::make('inmobiliaria')->label('Inmobiliaria*')->disabled(),
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
                            ]),

                        // ========== TAB: SOLICITUDES ==========
                        Forms\Components\Tabs\Tab::make('Solicitudes')
                            ->icon('heroicon-o-clipboard-document-list')
                            ->schema([
                                Forms\Components\Tabs::make('SolicitudesTabs')
                                    ->columnSpanFull()
                                    ->tabs([
                                        // Sub-tab: Inquilino
                                        Forms\Components\Tabs\Tab::make('Inquilino')
                                            ->icon('heroicon-o-user')
                                            ->schema([
                                                Forms\Components\Section::make('Datos del inquilino')
                                                    ->schema([
                                                        Forms\Components\Placeholder::make('current_tenant_info')
                                                            ->label('Información actual del inquilino')
                                                            ->content(function () {
                                                                $tenant = $this->record->tenant;
                                                                if (!$tenant) return 'No hay inquilino asignado';
                                                                if ($tenant->tipo_persona === 'fisica') {
                                                                    return "Tipo: Persona Física\nNombre: {$tenant->nombres} {$tenant->primer_apellido} {$tenant->segundo_apellido}\nEmail: {$tenant->email}";
                                                                }
                                                                return "Tipo: Persona Moral\nRazón Social: {$tenant->razon_social}\nEmail: {$tenant->email}\nRFC: {$tenant->rfc}";
                                                            })
                                                            ->columnSpanFull(),
                                                        
                                                        Forms\Components\Select::make('tenant_tipo_persona')
                                                            ->label('Tipo de Persona')
                                                            ->options(['fisica' => 'Persona física', 'moral' => 'Persona moral'])
                                                            ->live()
                                                            ->required()
                                                            ->columnSpanFull(),
                                                        
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
                                                            ->options(['masculino' => 'Masculino', 'femenino' => 'Femenino', 'otro' => 'Otro'])
                                                            ->placeholder('Seleccione')
                                                            ->visible(fn (Forms\Get $get) => $get('tenant_tipo_persona') === 'fisica'),
                                                        Forms\Components\TextInput::make('tenant_razon_social')
                                                            ->label('Nombre / Razón Social')
                                                            ->visible(fn (Forms\Get $get) => $get('tenant_tipo_persona') === 'moral')
                                                            ->columnSpan(2),
                                                        Forms\Components\TextInput::make('tenant_rfc')
                                                            ->label('RFC')
                                                            ->visible(fn (Forms\Get $get) => $get('tenant_tipo_persona') === 'moral'),
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
                                                                \Filament\Notifications\Notification::make()->success()->title('Inquilino actualizado')->send();
                                                                $this->redirect(RentResource::getUrl('view', ['record' => $this->record]));
                                                            }
                                                        }),
                                                    Forms\Components\Actions\Action::make('edit_tenant')
                                                        ->label('Editar solicitud del inquilino')
                                                        ->color('primary')
                                                        ->action(function () {
                                                            $tenantRequest = TenantRequest::where('tenant_id', $this->record->tenant_id)
                                                                ->where('rent_id', $this->record->id)->first();
                                                            if (!$tenantRequest) {
                                                                $tenantRequest = TenantRequest::create([
                                                                    'tenant_id' => $this->record->tenant_id,
                                                                    'rent_id' => $this->record->id,
                                                                    'estatus' => 'nueva',
                                                                    'nombres' => $this->record->tenant->nombres,
                                                                    'primer_apellido' => $this->record->tenant->primer_apellido,
                                                                    'segundo_apellido' => $this->record->tenant->segundo_apellido,
                                                                    'email' => $this->record->tenant->email,
                                                                    'rfc' => $this->record->tenant->rfc,
                                                                ]);
                                                            }
                                                            $this->redirect(TenantRequestResource::getUrl('edit', ['record' => $tenantRequest]));
                                                        })
                                                        ->visible(fn () => $this->record->tenant),
                                                    Forms\Components\Actions\Action::make('send_tenant')->label('Enviar solicitud al inquilino')->color('success'),
                                                    Forms\Components\Actions\Action::make('copy_link_tenant')->label('Copiar link')->color('gray'),
                                                    Forms\Components\Actions\Action::make('export_pdf_tenant')->label('Exportar PDF')->color('warning'),
                                                ]),
                                            ]),

                                        // Sub-tab: Fiador
                                        Forms\Components\Tabs\Tab::make('Fiador')
                                            ->icon('heroicon-o-hand-raised')
                                            ->schema([
                                                Forms\Components\Section::make('Datos del Obligado solidario / Fiador')
                                                    ->schema([
                                                        Forms\Components\Select::make('tiene_fiador')
                                                            ->label('¿Tiene fiador?')
                                                            ->options(['si' => 'Sí', 'no' => 'No'])
                                                            ->default('no'),
                                                    ]),
                                                
                                                Forms\Components\Actions::make([
                                                    Forms\Components\Actions\Action::make('actualizar_fiador')
                                                        ->label('Guardar')
                                                        ->color('primary')
                                                        ->icon('heroicon-o-check')
                                                        ->action(function () {
                                                            $this->save();
                                                            \Filament\Notifications\Notification::make()->success()->title('Datos del fiador guardados')->send();
                                                        }),
                                                    Forms\Components\Actions\Action::make('edit_guarantor')->label('Editar solicitud del fiador')->color('primary'),
                                                    Forms\Components\Actions\Action::make('send_guarantor')->label('Enviar solicitud al fiador')->color('success'),
                                                    Forms\Components\Actions\Action::make('copy_link_guarantor')->label('Copiar link')->color('gray'),
                                                ]),
                                            ]),

                                        // Sub-tab: Propietario
                                        Forms\Components\Tabs\Tab::make('Propietario')
                                            ->icon('heroicon-o-home')
                                            ->schema([
                                                Forms\Components\Section::make('Datos del propietario')
                                                    ->schema([
                                                        Forms\Components\Placeholder::make('current_owner_info')
                                                            ->label('Información actual del propietario')
                                                            ->content(function () {
                                                                $owner = $this->record->owner;
                                                                if (!$owner) return 'No hay propietario asignado';
                                                                if ($owner->tipo_persona === 'fisica') {
                                                                    return "Tipo: Persona Física\nNombre: {$owner->nombres} {$owner->primer_apellido} {$owner->segundo_apellido}\nEmail: {$owner->email}";
                                                                }
                                                                return "Tipo: Persona Moral\nRazón Social: {$owner->razon_social}\nEmail: {$owner->email}\nRFC: {$owner->rfc}";
                                                            })
                                                            ->columnSpanFull(),
                                                        
                                                        Forms\Components\Select::make('owner_tipo_persona')
                                                            ->label('Tipo de Persona')
                                                            ->options(['fisica' => 'Persona física', 'moral' => 'Persona moral'])
                                                            ->live()
                                                            ->required()
                                                            ->columnSpanFull(),
                                                        
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
                                                            ->options(['masculino' => 'Masculino', 'femenino' => 'Femenino', 'otro' => 'Otro'])
                                                            ->placeholder('Seleccione')
                                                            ->visible(fn (Forms\Get $get) => $get('owner_tipo_persona') === 'fisica'),
                                                        Forms\Components\TextInput::make('owner_razon_social')
                                                            ->label('Nombre / Razón Social')
                                                            ->visible(fn (Forms\Get $get) => $get('owner_tipo_persona') === 'moral')
                                                            ->columnSpan(2),
                                                        Forms\Components\TextInput::make('owner_rfc')
                                                            ->label('RFC')
                                                            ->visible(fn (Forms\Get $get) => $get('owner_tipo_persona') === 'moral'),
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
                                                                \Filament\Notifications\Notification::make()->success()->title('Propietario actualizado')->send();
                                                                $this->redirect(RentResource::getUrl('view', ['record' => $this->record]));
                                                            }
                                                        }),
                                                    Forms\Components\Actions\Action::make('edit_owner')
                                                        ->label('Editar solicitud del propietario')
                                                        ->color('primary')
                                                        ->action(function () {
                                                            $ownerRequest = OwnerRequest::where('owner_id', $this->record->owner_id)
                                                                ->where('rent_id', $this->record->id)->first();
                                                            if (!$ownerRequest) {
                                                                $ownerRequest = OwnerRequest::create([
                                                                    'owner_id' => $this->record->owner_id,
                                                                    'rent_id' => $this->record->id,
                                                                    'estatus' => 'nueva',
                                                                    'nombres' => $this->record->owner->nombres,
                                                                    'primer_apellido' => $this->record->owner->primer_apellido,
                                                                    'segundo_apellido' => $this->record->owner->segundo_apellido,
                                                                    'email' => $this->record->owner->email,
                                                                    'rfc' => $this->record->owner->rfc,
                                                                ]);
                                                            }
                                                            $this->redirect(OwnerRequestResource::getUrl('edit', ['record' => $ownerRequest]));
                                                        })
                                                        ->visible(fn () => $this->record->owner),
                                                    Forms\Components\Actions\Action::make('send_owner')->label('Enviar solicitud al propietario')->color('success'),
                                                    Forms\Components\Actions\Action::make('copy_link_owner')->label('Copiar link')->color('gray'),
                                                    Forms\Components\Actions\Action::make('export_pdf_owner')->label('Exportar PDF')->color('warning'),
                                                ]),
                                            ]),

                                        // Sub-tab: Propiedad
                                        Forms\Components\Tabs\Tab::make('Propiedad')
                                            ->icon('heroicon-o-building-office')
                                            ->schema([
                                                Forms\Components\Section::make('Datos de la propiedad')
                                                    ->schema([
                                                        Forms\Components\Select::make('tipo_propiedad')
                                                            ->label('Tipo de Propiedad')
                                                            ->options([
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
                                                        Forms\Components\TextInput::make('calle')->label('Calle'),
                                                        Forms\Components\TextInput::make('numero_exterior')->label('Núm Ext'),
                                                        Forms\Components\TextInput::make('numero_interior')->label('Núm Int'),
                                                        Forms\Components\Textarea::make('referencias_ubicacion')
                                                            ->label('Referencias Ubicación')
                                                            ->rows(2)
                                                            ->columnSpanFull(),
                                                        Forms\Components\TextInput::make('colonia')->label('Colonia'),
                                                        Forms\Components\TextInput::make('municipio')->label('Municipio/Alcaldía'),
                                                        Forms\Components\Select::make('estado')
                                                            ->label('Estado')
                                                            ->options([
                                                                'aguascalientes' => 'Aguascalientes',
                                                                'baja_california' => 'Baja California',
                                                                'cdmx' => 'Ciudad de México',
                                                                'jalisco' => 'Jalisco',
                                                                'mexico' => 'Estado de México',
                                                                'nuevo_leon' => 'Nuevo León',
                                                                'puebla' => 'Puebla',
                                                                'queretaro' => 'Querétaro',
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
                                                            \Filament\Notifications\Notification::make()->success()->title('Datos de propiedad guardados')->send();
                                                        }),
                                                ]),
                                            ]),
                                    ]),
                            ]),

                        // ========== TAB: DOCUMENTOS ==========
                        Forms\Components\Tabs\Tab::make('Documentos')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Forms\Components\Tabs::make('DocumentosTabs')
                                    ->columnSpanFull()
                                    ->tabs([
                                        // Documentos Inquilino
                                        Forms\Components\Tabs\Tab::make('Inquilino')
                                            ->icon('heroicon-o-user')
                                            ->schema([
                                                Forms\Components\Section::make('Documentos del Inquilino')
                                                    ->description(fn () => $this->record->tenant?->tipo_persona === 'moral' 
                                                        ? 'Documentos requeridos para Persona Moral' 
                                                        : 'Documentos requeridos para Persona Física')
                                                    ->schema([
                                                        Forms\Components\Placeholder::make('tenant_docs_list')
                                                            ->label('Documentos cargados')
                                                            ->content(function () {
                                                                $docs = $this->record->tenantDocuments;
                                                                if ($docs->isEmpty()) return 'No hay documentos cargados';
                                                                $tipos = $this->record->tenant?->tipo_persona === 'moral' 
                                                                    ? TenantDocument::tiposPersonaMoral() 
                                                                    : TenantDocument::tiposPersonaFisica();
                                                                return new \Illuminate\Support\HtmlString(
                                                                    $docs->map(function ($doc) use ($tipos) {
                                                                        $label = ($tipos[$doc->tag] ?? $doc->tag) . ' - ' . basename($doc->path_file);
                                                                        $url = Storage::disk('public')->url($doc->path_file);
                                                                        return "<div class='flex items-center gap-2 py-1'>
                                                                            <span class='flex-1'>{$label}</span>
                                                                            <a href='{$url}' target='_blank' class='text-blue-500 hover:text-blue-700' title='Ver'><svg class='w-5 h-5' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M15 12a3 3 0 11-6 0 3 3 0 016 0z'></path><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z'></path></svg></a>
                                                                            <a href='{$url}' download class='text-green-500 hover:text-green-700' title='Descargar'><svg class='w-5 h-5' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4'></path></svg></a>
                                                                            <button type='button' onclick=\"confirmDelete({$doc->id}, 'tenant')\" class='text-red-500 hover:text-red-700' title='Eliminar'><svg class='w-5 h-5' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16'></path></svg></button>
                                                                        </div>";
                                                                    })->implode('')
                                                                );
                                                            })
                                                            ->columnSpanFull(),
                                                    ]),
                                                Forms\Components\Actions::make([
                                                    Forms\Components\Actions\Action::make('subir_doc_inquilino')
                                                        ->label('Subir Documento')
                                                        ->color('primary')
                                                        ->icon('heroicon-o-arrow-up-tray')
                                                        ->form([
                                                            Forms\Components\Select::make('tag')
                                                                ->label('Tipo de Documento')
                                                                ->options(fn () => $this->record->tenant?->tipo_persona === 'moral' 
                                                                    ? TenantDocument::tiposPersonaMoral() 
                                                                    : TenantDocument::tiposPersonaFisica())
                                                                ->required(),
                                                            Forms\Components\FileUpload::make('file')
                                                                ->label('Archivo')
                                                                ->directory('tenant-documents')
                                                                ->acceptedFileTypes(['application/pdf', 'image/*'])
                                                                ->maxSize(5120)
                                                                ->required(),
                                                        ])
                                                        ->action(function (array $data) {
                                                            TenantDocument::create([
                                                                'rent_id' => $this->record->id,
                                                                'user_id' => auth()->id(),
                                                                'user_name' => auth()->user()->name,
                                                                'tag' => $data['tag'],
                                                                'path_file' => $data['file'],
                                                                'mime' => Storage::disk('public')->mimeType($data['file']) ?? 'application/octet-stream',
                                                            ]);
                                                            \Filament\Notifications\Notification::make()->success()->title('Documento subido correctamente')->send();
                                                        }),
                                                ]),
                                            ]),

                                        // Documentos Fiador
                                        Forms\Components\Tabs\Tab::make('Fiador')
                                            ->icon('heroicon-o-hand-raised')
                                            ->schema([
                                                Forms\Components\Section::make('Documentos del Fiador')
                                                    ->schema([
                                                        Forms\Components\Placeholder::make('guarantor_docs_list')
                                                            ->label('Documentos cargados')
                                                            ->content(function () {
                                                                $docs = $this->record->guarantorDocuments;
                                                                if ($docs->isEmpty()) return 'No hay documentos cargados';
                                                                $tipos = GuarantorDocument::tiposPersonaFisica();
                                                                return new \Illuminate\Support\HtmlString(
                                                                    $docs->map(function ($doc) use ($tipos) {
                                                                        $label = ($tipos[$doc->tag] ?? $doc->tag) . ' - ' . basename($doc->path_file);
                                                                        $url = Storage::disk('public')->url($doc->path_file);
                                                                        return "<div class='flex items-center gap-2 py-1'>
                                                                            <span class='flex-1'>{$label}</span>
                                                                            <a href='{$url}' target='_blank' class='text-blue-500 hover:text-blue-700' title='Ver'><svg class='w-5 h-5' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M15 12a3 3 0 11-6 0 3 3 0 016 0z'></path><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z'></path></svg></a>
                                                                            <a href='{$url}' download class='text-green-500 hover:text-green-700' title='Descargar'><svg class='w-5 h-5' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4'></path></svg></a>
                                                                            <button type='button' onclick=\"confirmDelete({$doc->id}, 'guarantor')\" class='text-red-500 hover:text-red-700' title='Eliminar'><svg class='w-5 h-5' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16'></path></svg></button>
                                                                        </div>";
                                                                    })->implode('')
                                                                );
                                                            })
                                                            ->columnSpanFull(),
                                                    ]),
                                                Forms\Components\Actions::make([
                                                    Forms\Components\Actions\Action::make('subir_doc_fiador')
                                                        ->label('Subir Documento')
                                                        ->color('primary')
                                                        ->icon('heroicon-o-arrow-up-tray')
                                                        ->form([
                                                            Forms\Components\Select::make('tag')
                                                                ->label('Tipo de Documento')
                                                                ->options(GuarantorDocument::tiposPersonaFisica())
                                                                ->required(),
                                                            Forms\Components\FileUpload::make('file')
                                                                ->label('Archivo')
                                                                ->directory('guarantor-documents')
                                                                ->acceptedFileTypes(['application/pdf', 'image/*'])
                                                                ->maxSize(5120)
                                                                ->required(),
                                                        ])
                                                        ->action(function (array $data) {
                                                            GuarantorDocument::create([
                                                                'rent_id' => $this->record->id,
                                                                'user_id' => auth()->id(),
                                                                'user_name' => auth()->user()->name,
                                                                'tag' => $data['tag'],
                                                                'path_file' => $data['file'],
                                                                'mime' => Storage::disk('public')->mimeType($data['file']) ?? 'application/octet-stream',
                                                            ]);
                                                            \Filament\Notifications\Notification::make()->success()->title('Documento subido correctamente')->send();
                                                        }),
                                                ]),
                                            ]),

                                        // Documentos Propietario
                                        Forms\Components\Tabs\Tab::make('Propietario')
                                            ->icon('heroicon-o-home')
                                            ->schema([
                                                Forms\Components\Section::make('Documentos del Propietario')
                                                    ->description(fn () => $this->record->owner?->tipo_persona === 'moral' 
                                                        ? 'Documentos requeridos para Persona Moral' 
                                                        : 'Documentos requeridos para Persona Física')
                                                    ->schema([
                                                        Forms\Components\Placeholder::make('owner_docs_list')
                                                            ->label('Documentos cargados')
                                                            ->content(function () {
                                                                $docs = $this->record->ownerDocuments;
                                                                if ($docs->isEmpty()) return 'No hay documentos cargados';
                                                                $tipos = $this->record->owner?->tipo_persona === 'moral' 
                                                                    ? OwnerDocument::tiposPersonaMoral() 
                                                                    : OwnerDocument::tiposPersonaFisica();
                                                                return new \Illuminate\Support\HtmlString(
                                                                    $docs->map(function ($doc) use ($tipos) {
                                                                        $label = ($tipos[$doc->tag] ?? $doc->tag) . ' - ' . basename($doc->path_file);
                                                                        $url = Storage::disk('public')->url($doc->path_file);
                                                                        return "<div class='flex items-center gap-2 py-1'>
                                                                            <span class='flex-1'>{$label}</span>
                                                                            <a href='{$url}' target='_blank' class='text-blue-500 hover:text-blue-700' title='Ver'><svg class='w-5 h-5' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M15 12a3 3 0 11-6 0 3 3 0 016 0z'></path><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z'></path></svg></a>
                                                                            <a href='{$url}' download class='text-green-500 hover:text-green-700' title='Descargar'><svg class='w-5 h-5' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4'></path></svg></a>
                                                                            <button type='button' onclick=\"confirmDelete({$doc->id}, 'owner')\" class='text-red-500 hover:text-red-700' title='Eliminar'><svg class='w-5 h-5' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16'></path></svg></button>
                                                                        </div>";
                                                                    })->implode('')
                                                                );
                                                            })
                                                            ->columnSpanFull(),
                                                    ]),
                                                Forms\Components\Actions::make([
                                                    Forms\Components\Actions\Action::make('subir_doc_propietario')
                                                        ->label('Subir Documento')
                                                        ->color('primary')
                                                        ->icon('heroicon-o-arrow-up-tray')
                                                        ->form([
                                                            Forms\Components\Select::make('tag')
                                                                ->label('Tipo de Documento')
                                                                ->options(fn () => $this->record->owner?->tipo_persona === 'moral' 
                                                                    ? OwnerDocument::tiposPersonaMoral() 
                                                                    : OwnerDocument::tiposPersonaFisica())
                                                                ->required(),
                                                            Forms\Components\FileUpload::make('file')
                                                                ->label('Archivo')
                                                                ->directory('owner-documents')
                                                                ->acceptedFileTypes(['application/pdf', 'image/*'])
                                                                ->maxSize(5120)
                                                                ->required(),
                                                        ])
                                                        ->action(function (array $data) {
                                                            OwnerDocument::create([
                                                                'rent_id' => $this->record->id,
                                                                'user_id' => auth()->id(),
                                                                'user_name' => auth()->user()->name,
                                                                'tag' => $data['tag'],
                                                                'path_file' => $data['file'],
                                                                'mime' => Storage::disk('public')->mimeType($data['file']) ?? 'application/octet-stream',
                                                            ]);
                                                            \Filament\Notifications\Notification::make()->success()->title('Documento subido correctamente')->send();
                                                        }),
                                                ]),
                                            ]),

                                        // Documentos Propiedad
                                        Forms\Components\Tabs\Tab::make('Propiedad')
                                            ->icon('heroicon-o-building-office')
                                            ->schema([
                                                Forms\Components\Section::make('Documentos de la Propiedad')
                                                    ->schema([
                                                        Forms\Components\Placeholder::make('property_docs_list')
                                                            ->label('Documentos cargados')
                                                            ->content(function () {
                                                                $docs = $this->record->propertyDocuments;
                                                                if ($docs->isEmpty()) return 'No hay documentos cargados';
                                                                $tipos = PropertyDocument::tipos();
                                                                return new \Illuminate\Support\HtmlString(
                                                                    $docs->map(function ($doc) use ($tipos) {
                                                                        $label = ($tipos[$doc->tag] ?? $doc->tag) . ' - ' . basename($doc->path_file);
                                                                        $url = Storage::disk('public')->url($doc->path_file);
                                                                        return "<div class='flex items-center gap-2 py-1'>
                                                                            <span class='flex-1'>{$label}</span>
                                                                            <a href='{$url}' target='_blank' class='text-blue-500 hover:text-blue-700' title='Ver'><svg class='w-5 h-5' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M15 12a3 3 0 11-6 0 3 3 0 016 0z'></path><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z'></path></svg></a>
                                                                            <a href='{$url}' download class='text-green-500 hover:text-green-700' title='Descargar'><svg class='w-5 h-5' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4'></path></svg></a>
                                                                            <button type='button' onclick=\"confirmDelete({$doc->id}, 'property')\" class='text-red-500 hover:text-red-700' title='Eliminar'><svg class='w-5 h-5' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16'></path></svg></button>
                                                                        </div>";
                                                                    })->implode('')
                                                                );
                                                            })
                                                            ->columnSpanFull(),
                                                    ]),
                                                Forms\Components\Actions::make([
                                                    Forms\Components\Actions\Action::make('subir_doc_propiedad')
                                                        ->label('Subir Documento')
                                                        ->color('primary')
                                                        ->icon('heroicon-o-arrow-up-tray')
                                                        ->form([
                                                            Forms\Components\Select::make('tag')
                                                                ->label('Tipo de Documento')
                                                                ->options(PropertyDocument::tipos())
                                                                ->required(),
                                                            Forms\Components\FileUpload::make('file')
                                                                ->label('Archivo')
                                                                ->directory('property-documents')
                                                                ->acceptedFileTypes(['application/pdf', 'image/*'])
                                                                ->maxSize(5120)
                                                                ->required(),
                                                        ])
                                                        ->action(function (array $data) {
                                                            PropertyDocument::create([
                                                                'rent_id' => $this->record->id,
                                                                'user_id' => auth()->id(),
                                                                'user_name' => auth()->user()->name,
                                                                'tag' => $data['tag'],
                                                                'path_file' => $data['file'],
                                                                'mime' => Storage::disk('public')->mimeType($data['file']) ?? 'application/octet-stream',
                                                            ]);
                                                            \Filament\Notifications\Notification::make()->success()->title('Documento subido correctamente')->send();
                                                        }),
                                                ]),
                                            ]),
                                    ]),
                            ]),

                        // ========== TAB: INVESTIGACIÓN ==========
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

                        // ========== TAB: COMENTARIOS ==========
                        Forms\Components\Tabs\Tab::make('Comentarios')
                            ->icon('heroicon-o-chat-bubble-left-right')
                            ->schema([
                                Forms\Components\Section::make('Comentarios de la Renta')
                                    ->schema([
                                        Forms\Components\Placeholder::make('comments_list')
                                            ->label('')
                                            ->content(function () {
                                                $comments = $this->record->comments()->with('user')->orderBy('created_at', 'desc')->get();
                                                if ($comments->isEmpty()) return 'No hay comentarios';
                                                return $comments->map(function ($comment) {
                                                    $date = $comment->created_at->format('d/m/Y H:i');
                                                    $user = $comment->user?->name ?? 'Usuario';
                                                    return "[{$date}] {$user}: {$comment->comment}";
                                                })->implode("\n\n");
                                            })
                                            ->columnSpanFull(),
                                    ]),
                                Forms\Components\Actions::make([
                                    Forms\Components\Actions\Action::make('agregar_comentario')
                                        ->label('Agregar Comentario')
                                        ->color('primary')
                                        ->icon('heroicon-o-plus')
                                        ->form([
                                            Forms\Components\Textarea::make('comment')
                                                ->label('Comentario')
                                                ->required()
                                                ->rows(4),
                                        ])
                                        ->action(function (array $data) {
                                            RentComment::create([
                                                'rent_id' => $this->record->id,
                                                'user_id' => auth()->id(),
                                                'comment' => $data['comment'],
                                                'status' => 'activo',
                                            ]);
                                            \Filament\Notifications\Notification::make()->success()->title('Comentario agregado')->send();
                                        }),
                                ]),
                            ]),
                    ]),
            ]);
    }
}
