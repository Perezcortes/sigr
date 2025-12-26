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
use App\Models\Application;
use App\Models\Owner;
use App\Models\Property;
use App\Filament\Resources\TenantRequestResource;
use App\Filament\Resources\OwnerRequestResource;
use App\Filament\Resources\ApplicationsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms;
use Filament\Forms\Form;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;

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

        if ($this->record->application_id) {
            $this->data['application_id'] = $this->record->application_id;
        }

        if ($this->record->owner_id) {
            $this->data['owner_id'] = $this->record->owner_id;
        }

        if ($this->record->property_id) {
            $this->data['property_id'] = $this->record->property_id;
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
                                            ->label('Tipo de inmueble')
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

                                                        // === SELECT DE APPLICATIONS ACTIVAS ===
                                                        Forms\Components\Select::make('application_id')
                                                            ->label('Seleccionar Solicitud Activa')
                                                            ->options(function () {
                                                                // Mostrar todas las Applications activas
                                                                $applications = Application::where('estatus', 'activa')
                                                                    ->with('user.tenant')
                                                                    ->orderBy('created_at', 'desc')
                                                                    ->get();
                                                                
                                                                $options = [];
                                                                foreach ($applications as $application) {
                                                                    $tenant = $application->user->tenant ?? null;
                                                                    if ($tenant) {
                                                                        if ($tenant->tipo_persona === 'fisica') {
                                                                            $nombre = trim(($tenant->nombres ?? '') . ' ' . ($tenant->primer_apellido ?? '') . ' ' . ($tenant->segundo_apellido ?? ''));
                                                                        } else {
                                                                            $nombre = $tenant->razon_social ?? '';
                                                                        }
                                                                        $label = ($nombre ?: 'Sin nombre') . ' - ' . ($application->folio ?? 'N/A');
                                                                    } else {
                                                                        $label = ($application->user->name ?? 'Usuario') . ' - ' . ($application->folio ?? 'N/A');
                                                                    }
                                                                    $options[$application->id] = $label;
                                                                }
                                                                
                                                                return $options;
                                                            })
                                                            ->searchable()
                                                            ->preload()
                                                            ->live()
                                                            ->placeholder('Seleccione una solicitud activa')
                                                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                                                if ($state) {
                                                                    $application = Application::with('user.tenant')->find($state);
                                                                    if ($application && $application->user && $application->user->tenant) {
                                                                        $tenant = $application->user->tenant;
                                                                        
                                                                        // Actualizar application_id y tenant_id en la rent
                                                                        $this->record->update([
                                                                            'application_id' => $state,
                                                                            'tenant_id' => $tenant->id,
                                                                        ]);
                                                                        
                                                                        // Recargar la relación tenant
                                                                        $this->record->load('tenant');
                                                                        
                                                                        // Pre-llenar datos básicos del tenant
                                                                        $set('tenant_tipo_persona', $tenant->tipo_persona);
                                                                        if ($tenant->tipo_persona === 'fisica') {
                                                                            $set('tenant_nombres', $tenant->nombres);
                                                                            $set('tenant_primer_apellido', $tenant->primer_apellido);
                                                                            $set('tenant_segundo_apellido', $tenant->segundo_apellido);
                                                                            $set('tenant_sexo', $tenant->sexo);
                                                                        } else {
                                                                            $set('tenant_razon_social', $tenant->razon_social);
                                                                            $set('tenant_rfc', $tenant->rfc);
                                                                        }
                                                                        $set('tenant_email', $tenant->email ?? $application->user->email);
                                                                        
                                                                        \Filament\Notifications\Notification::make()
                                                                            ->success()
                                                                            ->title('Solicitud vinculada')
                                                                            ->body('Los datos del tenant se han actualizado.')
                                                                            ->send();
                                                                    }
                                                                }
                                                            }),
                                                        // === FIN SELECT DE APPLICATIONS ===
                                                        
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
                                                                
                                                                // Persistir application_id y actualizar tenant_id si está presente
                                                                if (isset($this->data['application_id'])) {
                                                                    $application = Application::with('user.tenant')->find($this->data['application_id']);
                                                                    if ($application && $application->user && $application->user->tenant) {
                                                                        $this->record->update([
                                                                            'application_id' => $this->data['application_id'],
                                                                            'tenant_id' => $application->user->tenant->id,
                                                                        ]);
                                                                    } else {
                                                                        $this->record->update(['application_id' => $this->data['application_id']]);
                                                                    }
                                                                }
                                                                
                                                                \Filament\Notifications\Notification::make()->success()->title('Inquilino actualizado')->send();
                                                                $this->redirect(RentResource::getUrl('view', ['record' => $this->record]));
                                                            }
                                                        }),
                                                    Forms\Components\Actions\Action::make('edit_tenant')
                                                        ->label('Editar solicitud del inquilino')
                                                        ->color('primary')
                                                        ->action(function () {
                                                            // Si hay una Application vinculada, usar esa
                                                            if ($this->record->application_id) {
                                                                $this->redirect(ApplicationsResource::getUrl('edit', ['record' => $this->record->application_id]));
                                                                return;
                                                            }
                                                            
                                                            // Si no hay Application, usar el flujo anterior con TenantRequest
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
                                                        // === SELECT DE OWNERS ===
                                                        Forms\Components\Select::make('owner_id')
                                                            ->label('Seleccionar Propietario')
                                                            ->options(function () {
                                                                // Mostrar todos los Owners disponibles (usuarios con is_owner = true)
                                                                $owners = Owner::with('user')
                                                                    ->whereHas('user', function (Builder $query) {
                                                                        $query->where('is_owner', true);
                                                                    })
                                                                    ->orderBy('created_at', 'desc')
                                                                    ->get();
                                                                
                                                                $options = [];
                                                                foreach ($owners as $owner) {
                                                                    if ($owner->tipo_persona === 'fisica') {
                                                                        $nombre = trim(($owner->nombres ?? '') . ' ' . ($owner->primer_apellido ?? '') . ' ' . ($owner->segundo_apellido ?? ''));
                                                                    } else {
                                                                        $nombre = $owner->razon_social ?? '';
                                                                    }
                                                                    $label = ($nombre ?: 'Sin nombre') . ' - ' . ($owner->email ?? '');
                                                                    $options[$owner->id] = $label;
                                                                }
                                                                
                                                                return $options;
                                                            })
                                                            ->getOptionLabelUsing(function ($value) {
                                                                if (!$value) return null;
                                                                $owner = Owner::find($value);
                                                                if (!$owner) return $value;
                                                                
                                                                if ($owner->tipo_persona === 'fisica') {
                                                                    $nombre = trim(($owner->nombres ?? '') . ' ' . ($owner->primer_apellido ?? '') . ' ' . ($owner->segundo_apellido ?? ''));
                                                                } else {
                                                                    $nombre = $owner->razon_social ?? '';
                                                                }
                                                                return ($nombre ?: 'Sin nombre') . ' - ' . ($owner->email ?? '');
                                                            })
                                                            ->searchable()
                                                            ->preload()
                                                            ->live()
                                                            ->placeholder('Seleccione un propietario')
                                                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                                                if ($state) {
                                                                    $owner = Owner::find($state);
                                                                    if ($owner) {
                                                                        // Actualizar owner_id en la rent
                                                                        $this->record->update(['owner_id' => $state]);
                                                                        
                                                                        // Recargar la relación owner
                                                                        $this->record->load('owner');
                                                                        
                                                                        // Pre-llenar datos básicos del owner
                                                                        $set('owner_tipo_persona', $owner->tipo_persona);
                                                                        if ($owner->tipo_persona === 'fisica') {
                                                                            $set('owner_nombres', $owner->nombres);
                                                                            $set('owner_primer_apellido', $owner->primer_apellido);
                                                                            $set('owner_segundo_apellido', $owner->segundo_apellido);
                                                                            $set('owner_sexo', $owner->sexo);
                                                                        } else {
                                                                            $set('owner_razon_social', $owner->razon_social);
                                                                            $set('owner_rfc', $owner->rfc);
                                                                        }
                                                                        $set('owner_email', $owner->email);
                                                                        
                                                                        \Filament\Notifications\Notification::make()
                                                                            ->success()
                                                                            ->title('Propietario vinculado')
                                                                            ->body('Los datos del propietario se han actualizado.')
                                                                            ->send();
                                                                    }
                                                                }
                                                            }),
                                                        // === FIN SELECT DE OWNERS ===
                                                        
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
                                                                
                                                                // Persistir owner_id si está presente
                                                                if (isset($this->data['owner_id'])) {
                                                                    $this->record->update(['owner_id' => $this->data['owner_id']]);
                                                                }
                                                                
                                                                \Filament\Notifications\Notification::make()->success()->title('Propietario actualizado')->send();
                                                                $this->redirect(RentResource::getUrl('view', ['record' => $this->record]));
                                                            }
                                                        }),
                                                    Forms\Components\Actions\Action::make('edit_owner')
                                                        ->label('Ver solicitud del propietario')
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
                                                        // === SELECT DE PROPERTIES DISPONIBLES ===
                                                        Forms\Components\Select::make('property_id')
                                                            ->label('Seleccionar Propiedad Disponible')
                                                            ->options(function () {
                                                                // Mostrar solo Properties con estatus "disponible"
                                                                $properties = Property::where('estatus', 'disponible')
                                                                    ->orderBy('created_at', 'desc')
                                                                    ->get();
                                                                
                                                                $options = [];
                                                                foreach ($properties as $property) {
                                                                    $direccion = trim(($property->calle ?? '') . ' ' . ($property->numero_exterior ?? ''));
                                                                    $label = ($property->folio ?? 'N/A') . ' - ' . ($direccion ?: 'Sin dirección');
                                                                    $options[$property->id] = $label;
                                                                }
                                                                
                                                                return $options;
                                                            })
                                                            ->getOptionLabelUsing(function ($value) {
                                                                if (!$value) return null;
                                                                $property = Property::find($value);
                                                                if (!$property) return $value;
                                                                
                                                                $direccion = trim(($property->calle ?? '') . ' ' . ($property->numero_exterior ?? ''));
                                                                return ($property->folio ?? 'N/A') . ' - ' . ($direccion ?: 'Sin dirección');
                                                            })
                                                            ->searchable()
                                                            ->preload()
                                                            ->live()
                                                            ->placeholder('Seleccione una propiedad disponible')
                                                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                                                if ($state) {
                                                                    $property = Property::find($state);
                                                                    if ($property) {
                                                                        // Actualizar property_id en la rent
                                                                        $this->record->update(['property_id' => $state]);
                                                                        
                                                                        // Copiar todos los datos de la propiedad a los campos de la rent
                                                                        $set('tipo_propiedad', $property->tipo_inmueble ?? '');
                                                                        $set('calle', $property->calle ?? '');
                                                                        $set('numero_exterior', $property->numero_exterior ?? '');
                                                                        $set('numero_interior', $property->numero_interior ?? '');
                                                                        $set('codigo_postal', $property->codigo_postal ?? '');
                                                                        $set('colonia', $property->colonia ?? '');
                                                                        $set('municipio', $property->delegacion_municipio ?? '');
                                                                        $set('estado', $property->estado ?? '');
                                                                        $set('referencias_ubicacion', $property->referencias_ubicacion ?? '');
                                                                        
                                                                        \Filament\Notifications\Notification::make()
                                                                            ->success()
                                                                            ->title('Propiedad seleccionada')
                                                                            ->body('Los datos de la propiedad se han cargado. Haga clic en Guardar para persistir los cambios.')
                                                                            ->send();
                                                                    }
                                                                }
                                                            }),
                                                        // === FIN SELECT DE PROPERTIES ===
                                                        
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
                                                                'Aguascalientes' => 'Aguascalientes',
                                                                'Baja California' => 'Baja California',
                                                                'Baja California Sur' => 'Baja California Sur',
                                                                'Campeche' => 'Campeche',
                                                                'Chiapas' => 'Chiapas',
                                                                'Chihuahua' => 'Chihuahua',
                                                                'Ciudad de México' => 'Ciudad de México',
                                                                'Coahuila' => 'Coahuila',
                                                                'Colima' => 'Colima',
                                                                'Durango' => 'Durango',
                                                                'Estado de México' => 'Estado de México',
                                                                'Guanajuato' => 'Guanajuato',
                                                                'Guerrero' => 'Guerrero',
                                                                'Hidalgo' => 'Hidalgo',
                                                                'Jalisco' => 'Jalisco',
                                                                'Michoacán' => 'Michoacán',
                                                                'Morelos' => 'Morelos',
                                                                'Nayarit' => 'Nayarit',
                                                                'Nuevo León' => 'Nuevo León',
                                                                'Oaxaca' => 'Oaxaca',
                                                                'Puebla' => 'Puebla',
                                                                'Querétaro' => 'Querétaro',
                                                                'Quintana Roo' => 'Quintana Roo',
                                                                'San Luis Potosí' => 'San Luis Potosí',
                                                                'Sinaloa' => 'Sinaloa',
                                                                'Sonora' => 'Sonora',
                                                                'Tabasco' => 'Tabasco',
                                                                'Tamaulipas' => 'Tamaulipas',
                                                                'Tlaxcala' => 'Tlaxcala',
                                                                'Veracruz' => 'Veracruz',
                                                                'Yucatán' => 'Yucatán',
                                                                'Zacatecas' => 'Zacatecas',
                                                            ])
                                                            ->disabled()   
                                                            ->dehydrated() 
                                                            ->required(),
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
                                                            // Persistir property_id si está presente
                                                            if (isset($this->data['property_id'])) {
                                                                $this->record->update(['property_id' => $this->data['property_id']]);
                                                            }
                                                            
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
                                        
                                        // === PESTAÑA: INQUILINO ===
                                        Forms\Components\Tabs\Tab::make('Inquilino')
                                            ->icon('heroicon-o-user')
                                            ->schema([
                                                Forms\Components\Section::make('Expediente del Inquilino')
                                                    ->description(fn () => $this->record->tenant?->tipo_persona === 'moral' 
                                                        ? 'Documentación fiscal y legal (Persona Moral)' 
                                                        : 'Documentación de identidad (Persona Física)')
                                                    ->headerActions([
                                                        Forms\Components\Actions\Action::make('subir_doc_inquilino')
                                                            ->label('Nuevo Documento')
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
                                                                    ->label('Seleccionar Archivo')
                                                                    ->directory('tenant-documents')
                                                                    ->acceptedFileTypes(['application/pdf', 'image/*'])
                                                                    ->maxSize(10240)
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
                                                                \Filament\Notifications\Notification::make()->success()->title('Documento cargado')->send();
                                                            }),
                                                    ])
                                                    ->schema([
                                                        Forms\Components\Placeholder::make('tenant_docs_grid')
                                                            ->hiddenLabel()
                                                            ->content(function () {
                                                                $docs = $this->record->tenantDocuments;
                                                                if ($docs->isEmpty()) {
                                                                    return new \Illuminate\Support\HtmlString('
                                                                        <div class="flex flex-col items-center justify-center p-6 border-2 border-dashed border-gray-300 rounded-xl dark:border-gray-700">
                                                                            <div class="p-3 bg-gray-100 rounded-full dark:bg-gray-800">
                                                                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                                                            </div>
                                                                            <p class="mt-2 text-sm text-gray-500">No hay documentos cargados aún.</p>
                                                                        </div>
                                                                    ');
                                                                }

                                                                $tipos = $this->record->tenant?->tipo_persona === 'moral' 
                                                                    ? TenantDocument::tiposPersonaMoral() 
                                                                    : TenantDocument::tiposPersonaFisica();

                                                                $html = '<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">';
                                                                foreach ($docs as $doc) {
                                                                    $url = Storage::disk('public')->url($doc->path_file);
                                                                    $name = basename($doc->path_file);
                                                                    $typeLabel = $tipos[$doc->tag] ?? $doc->tag;
                                                                    $isPdf = str_ends_with(strtolower($doc->path_file), '.pdf');
                                                                    
                                                                    // NOTA: Aquí corregí las comillas dentro del SVG (ahora son simples ' ')
                                                                    $icon = $isPdf 
                                                                        ? '<svg class="w-8 h-8 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>'
                                                                        : '<svg class="w-8 h-8 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>';

                                                                    $html .= "
                                                                    <div class='relative group bg-white border border-gray-200 rounded-xl p-4 hover:shadow-lg transition-all duration-200 dark:bg-gray-800 dark:border-gray-700'>
                                                                        <div class='flex items-start justify-between'>
                                                                            <div class='flex items-center gap-3'>
                                                                                <div class='p-2 bg-gray-50 rounded-lg dark:bg-gray-700'>{$icon}</div>
                                                                                <div class='overflow-hidden'>
                                                                                    <h4 class='text-sm font-bold text-[#161848] dark:text-white truncate' title='{$typeLabel}'>{$typeLabel}</h4>
                                                                                    <p class='text-xs text-gray-500 truncate mt-0.5' title='{$name}'>{$name}</p>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class='flex items-center justify-end gap-2 mt-4 pt-3 border-t border-gray-100 dark:border-gray-700'>
                                                                            <a href='{$url}' target='_blank' class='p-1.5 text-gray-500 hover:text-[#26cad3] hover:bg-[#26cad3]/10 rounded-lg transition-colors' title='Ver'>
                                                                                <svg class='w-4 h-4' fill='none' viewBox='0 0 24 24' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M15 12a3 3 0 11-6 0 3 3 0 016 0z' /><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z' /></svg>
                                                                            </a>
                                                                            <a href='{$url}' download class='p-1.5 text-gray-500 hover:text-green-600 hover:bg-green-50 rounded-lg transition-colors' title='Descargar'>
                                                                                <svg class='w-4 h-4' fill='none' viewBox='0 0 24 24' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4' /></svg>
                                                                            </a>
                                                                            <button onclick=\"confirmDelete({$doc->id}, 'tenant')\" class='p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors' title='Eliminar'>
                                                                                <svg class='w-4 h-4' fill='none' viewBox='0 0 24 24' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16' /></svg>
                                                                            </button>
                                                                        </div>
                                                                    </div>";
                                                                }
                                                                $html .= '</div>';
                                                                return new \Illuminate\Support\HtmlString($html);
                                                            }),
                                                    ]),
                                            ]),

                                        // === PESTAÑA: FIADOR ===
                                        Forms\Components\Tabs\Tab::make('Fiador')
                                            ->icon('heroicon-o-hand-raised')
                                            ->schema([
                                                Forms\Components\Section::make('Expediente del Fiador')
                                                    ->description('Documentos de garantía y respaldo')
                                                    ->headerActions([
                                                        Forms\Components\Actions\Action::make('subir_doc_fiador')
                                                            ->label('Nuevo Documento')
                                                            ->color('primary')
                                                            ->icon('heroicon-o-arrow-up-tray')
                                                            ->form([
                                                                Forms\Components\Select::make('tag')
                                                                    ->label('Tipo de Documento')
                                                                    ->options(GuarantorDocument::tiposPersonaFisica())
                                                                    ->required(),
                                                                Forms\Components\FileUpload::make('file')
                                                                    ->label('Seleccionar Archivo')
                                                                    ->directory('guarantor-documents')
                                                                    ->acceptedFileTypes(['application/pdf', 'image/*'])
                                                                    ->maxSize(10240)
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
                                                                \Filament\Notifications\Notification::make()->success()->title('Documento cargado')->send();
                                                            }),
                                                    ])
                                                    ->schema([
                                                        Forms\Components\Placeholder::make('guarantor_docs_grid')
                                                            ->hiddenLabel()
                                                            ->content(function () {
                                                                $docs = $this->record->guarantorDocuments;
                                                                if ($docs->isEmpty()) {
                                                                    return new \Illuminate\Support\HtmlString('
                                                                        <div class="flex flex-col items-center justify-center p-6 border-2 border-dashed border-gray-300 rounded-xl dark:border-gray-700">
                                                                            <div class="p-3 bg-gray-100 rounded-full dark:bg-gray-800">
                                                                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                                                            </div>
                                                                            <p class="mt-2 text-sm text-gray-500">No hay documentos de fiador cargados.</p>
                                                                        </div>
                                                                    ');
                                                                }
                                                                $tipos = GuarantorDocument::tiposPersonaFisica();
                                                                $html = '<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">';
                                                                foreach ($docs as $doc) {
                                                                    $url = Storage::disk('public')->url($doc->path_file);
                                                                    $name = basename($doc->path_file);
                                                                    $typeLabel = $tipos[$doc->tag] ?? $doc->tag;
                                                                    $isPdf = str_ends_with(strtolower($doc->path_file), '.pdf');
                                                                    
                                                                    $icon = $isPdf 
                                                                        ? '<svg class="w-8 h-8 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>'
                                                                        : '<svg class="w-8 h-8 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>';

                                                                    $html .= "
                                                                    <div class='relative group bg-white border border-gray-200 rounded-xl p-4 hover:shadow-lg transition-all duration-200 dark:bg-gray-800 dark:border-gray-700'>
                                                                        <div class='flex items-start justify-between'>
                                                                            <div class='flex items-center gap-3'>
                                                                                <div class='p-2 bg-gray-50 rounded-lg dark:bg-gray-700'>{$icon}</div>
                                                                                <div class='overflow-hidden'>
                                                                                    <h4 class='text-sm font-bold text-[#161848] dark:text-white truncate' title='{$typeLabel}'>{$typeLabel}</h4>
                                                                                    <p class='text-xs text-gray-500 truncate mt-0.5' title='{$name}'>{$name}</p>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class='flex items-center justify-end gap-2 mt-4 pt-3 border-t border-gray-100 dark:border-gray-700'>
                                                                            <a href='{$url}' target='_blank' class='p-1.5 text-gray-500 hover:text-[#26cad3] hover:bg-[#26cad3]/10 rounded-lg transition-colors' title='Ver'>
                                                                                <svg class='w-4 h-4' fill='none' viewBox='0 0 24 24' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M15 12a3 3 0 11-6 0 3 3 0 016 0z' /><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z' /></svg>
                                                                            </a>
                                                                            <a href='{$url}' download class='p-1.5 text-gray-500 hover:text-green-600 hover:bg-green-50 rounded-lg transition-colors' title='Descargar'>
                                                                                <svg class='w-4 h-4' fill='none' viewBox='0 0 24 24' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4' /></svg>
                                                                            </a>
                                                                            <button onclick=\"confirmDelete({$doc->id}, 'guarantor')\" class='p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors' title='Eliminar'>
                                                                                <svg class='w-4 h-4' fill='none' viewBox='0 0 24 24' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16' /></svg>
                                                                            </button>
                                                                        </div>
                                                                    </div>";
                                                                }
                                                                $html .= '</div>';
                                                                return new \Illuminate\Support\HtmlString($html);
                                                            }),
                                                    ]),
                                            ]),

                                        // === PESTAÑA: PROPIETARIO ===
                                        Forms\Components\Tabs\Tab::make('Propietario')
                                            ->icon('heroicon-o-home')
                                            ->schema([
                                                Forms\Components\Section::make('Expediente del Propietario')
                                                    ->description('Documentación legal de la propiedad y dueño')
                                                    ->headerActions([
                                                        Forms\Components\Actions\Action::make('subir_doc_propietario')
                                                            ->label('Nuevo Documento')
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
                                                                    ->label('Seleccionar Archivo')
                                                                    ->directory('owner-documents')
                                                                    ->acceptedFileTypes(['application/pdf', 'image/*'])
                                                                    ->maxSize(10240)
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
                                                                \Filament\Notifications\Notification::make()->success()->title('Documento cargado')->send();
                                                            }),
                                                    ])
                                                    ->schema([
                                                        Forms\Components\Placeholder::make('owner_docs_grid')
                                                            ->hiddenLabel()
                                                            ->content(function () {
                                                                $docs = $this->record->ownerDocuments;
                                                                if ($docs->isEmpty()) {
                                                                    return new \Illuminate\Support\HtmlString('
                                                                        <div class="flex flex-col items-center justify-center p-6 border-2 border-dashed border-gray-300 rounded-xl dark:border-gray-700">
                                                                            <div class="p-3 bg-gray-100 rounded-full dark:bg-gray-800">
                                                                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                                                            </div>
                                                                            <p class="mt-2 text-sm text-gray-500">No hay documentos cargados aún.</p>
                                                                        </div>
                                                                    ');
                                                                }
                                                                $tipos = $this->record->owner?->tipo_persona === 'moral' 
                                                                    ? OwnerDocument::tiposPersonaMoral() 
                                                                    : OwnerDocument::tiposPersonaFisica();
                                                                $html = '<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">';
                                                                foreach ($docs as $doc) {
                                                                    $url = Storage::disk('public')->url($doc->path_file);
                                                                    $name = basename($doc->path_file);
                                                                    $typeLabel = $tipos[$doc->tag] ?? $doc->tag;
                                                                    $isPdf = str_ends_with(strtolower($doc->path_file), '.pdf');
                                                                    
                                                                    $icon = $isPdf 
                                                                        ? '<svg class="w-8 h-8 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>'
                                                                        : '<svg class="w-8 h-8 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>';

                                                                    $html .= "
                                                                    <div class='relative group bg-white border border-gray-200 rounded-xl p-4 hover:shadow-lg transition-all duration-200 dark:bg-gray-800 dark:border-gray-700'>
                                                                        <div class='flex items-start justify-between'>
                                                                            <div class='flex items-center gap-3'>
                                                                                <div class='p-2 bg-gray-50 rounded-lg dark:bg-gray-700'>{$icon}</div>
                                                                                <div class='overflow-hidden'>
                                                                                    <h4 class='text-sm font-bold text-[#161848] dark:text-white truncate' title='{$typeLabel}'>{$typeLabel}</h4>
                                                                                    <p class='text-xs text-gray-500 truncate mt-0.5' title='{$name}'>{$name}</p>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class='flex items-center justify-end gap-2 mt-4 pt-3 border-t border-gray-100 dark:border-gray-700'>
                                                                            <a href='{$url}' target='_blank' class='p-1.5 text-gray-500 hover:text-[#26cad3] hover:bg-[#26cad3]/10 rounded-lg transition-colors' title='Ver'>
                                                                                <svg class='w-4 h-4' fill='none' viewBox='0 0 24 24' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M15 12a3 3 0 11-6 0 3 3 0 016 0z' /><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z' /></svg>
                                                                            </a>
                                                                            <a href='{$url}' download class='p-1.5 text-gray-500 hover:text-green-600 hover:bg-green-50 rounded-lg transition-colors' title='Descargar'>
                                                                                <svg class='w-4 h-4' fill='none' viewBox='0 0 24 24' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4' /></svg>
                                                                            </a>
                                                                            <button onclick=\"confirmDelete({$doc->id}, 'owner')\" class='p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors' title='Eliminar'>
                                                                                <svg class='w-4 h-4' fill='none' viewBox='0 0 24 24' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16' /></svg>
                                                                            </button>
                                                                        </div>
                                                                    </div>";
                                                                }
                                                                $html .= '</div>';
                                                                return new \Illuminate\Support\HtmlString($html);
                                                            }),
                                                    ]),
                                            ]),

                                        // === PESTAÑA: PROPIEDAD ===
                                        Forms\Components\Tabs\Tab::make('Propiedad')
                                            ->icon('heroicon-o-building-office')
                                            ->schema([
                                                Forms\Components\Section::make('Expediente del Inmueble')
                                                    ->description('Documentos técnicos y legales de la propiedad')
                                                    ->headerActions([
                                                        Forms\Components\Actions\Action::make('subir_doc_propiedad')
                                                            ->label('Nuevo Documento')
                                                            ->color('primary')
                                                            ->icon('heroicon-o-arrow-up-tray')
                                                            ->form([
                                                                Forms\Components\Select::make('tag')
                                                                    ->label('Tipo de Documento')
                                                                    ->options(PropertyDocument::tipos())
                                                                    ->required(),
                                                                Forms\Components\FileUpload::make('file')
                                                                    ->label('Seleccionar Archivo')
                                                                    ->directory('property-documents')
                                                                    ->acceptedFileTypes(['application/pdf', 'image/*'])
                                                                    ->maxSize(10240)
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
                                                                \Filament\Notifications\Notification::make()->success()->title('Documento cargado')->send();
                                                            }),
                                                    ])
                                                    ->schema([
                                                        Forms\Components\Placeholder::make('property_docs_grid')
                                                            ->hiddenLabel()
                                                            ->content(function () {
                                                                $docs = $this->record->propertyDocuments;
                                                                if ($docs->isEmpty()) {
                                                                    return new \Illuminate\Support\HtmlString('
                                                                        <div class="flex flex-col items-center justify-center p-6 border-2 border-dashed border-gray-300 rounded-xl dark:border-gray-700">
                                                                            <div class="p-3 bg-gray-100 rounded-full dark:bg-gray-800">
                                                                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                                                            </div>
                                                                            <p class="mt-2 text-sm text-gray-500">No hay documentos de la propiedad cargados.</p>
                                                                        </div>
                                                                    ');
                                                                }
                                                                $tipos = PropertyDocument::tipos();
                                                                $html = '<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">';
                                                                foreach ($docs as $doc) {
                                                                    $url = Storage::disk('public')->url($doc->path_file);
                                                                    $name = basename($doc->path_file);
                                                                    $typeLabel = $tipos[$doc->tag] ?? $doc->tag;
                                                                    $isPdf = str_ends_with(strtolower($doc->path_file), '.pdf');
                                                                    
                                                                    $icon = $isPdf 
                                                                        ? '<svg class="w-8 h-8 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>'
                                                                        : '<svg class="w-8 h-8 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>';

                                                                    $html .= "
                                                                    <div class='relative group bg-white border border-gray-200 rounded-xl p-4 hover:shadow-lg transition-all duration-200 dark:bg-gray-800 dark:border-gray-700'>
                                                                        <div class='flex items-start justify-between'>
                                                                            <div class='flex items-center gap-3'>
                                                                                <div class='p-2 bg-gray-50 rounded-lg dark:bg-gray-700'>{$icon}</div>
                                                                                <div class='overflow-hidden'>
                                                                                    <h4 class='text-sm font-bold text-[#161848] dark:text-white truncate' title='{$typeLabel}'>{$typeLabel}</h4>
                                                                                    <p class='text-xs text-gray-500 truncate mt-0.5' title='{$name}'>{$name}</p>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class='flex items-center justify-end gap-2 mt-4 pt-3 border-t border-gray-100 dark:border-gray-700'>
                                                                            <a href='{$url}' target='_blank' class='p-1.5 text-gray-500 hover:text-[#26cad3] hover:bg-[#26cad3]/10 rounded-lg transition-colors' title='Ver'>
                                                                                <svg class='w-4 h-4' fill='none' viewBox='0 0 24 24' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M15 12a3 3 0 11-6 0 3 3 0 016 0z' /><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z' /></svg>
                                                                            </a>
                                                                            <a href='{$url}' download class='p-1.5 text-gray-500 hover:text-green-600 hover:bg-green-50 rounded-lg transition-colors' title='Descargar'>
                                                                                <svg class='w-4 h-4' fill='none' viewBox='0 0 24 24' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4' /></svg>
                                                                            </a>
                                                                            <button onclick=\"confirmDelete({$doc->id}, 'property')\" class='p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors' title='Eliminar'>
                                                                                <svg class='w-4 h-4' fill='none' viewBox='0 0 24 24' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16' /></svg>
                                                                            </button>
                                                                        </div>
                                                                    </div>";
                                                                }
                                                                $html .= '</div>';
                                                                return new \Illuminate\Support\HtmlString($html);
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
                                Forms\Components\Section::make('Comentarios')
                                    ->description('Bitácora de seguimiento y notas internas.')
                                    ->schema([
                                        
                                        // === LISTA DE COMENTARIOS CON ESTILO CHAT ===
                                        Forms\Components\Placeholder::make('comments_list')
                                            ->label('') // Sin etiqueta para limpieza visual
                                            ->columnSpanFull()
                                            ->content(function () {
                                                $comments = $this->record->comments()
                                                    ->with('user')
                                                    ->orderBy('created_at', 'desc') // Los más nuevos arriba
                                                    ->get();

                                                if ($comments->isEmpty()) {
                                                    // DISEÑO DE ESTADO VACÍO (EMPTY STATE)
                                                    return new \Illuminate\Support\HtmlString('
                                                        <div class="flex flex-col items-center justify-center p-8 text-center bg-gray-50 border border-gray-200 border-dashed rounded-xl dark:bg-white/5 dark:border-white/10">
                                                            <div class="p-3 bg-white rounded-full shadow-sm dark:bg-gray-800">
                                                                <svg class="w-8 h-8 text-[#26cad3]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
                                                                </svg>
                                                            </div>
                                                            <h3 class="mt-2 text-sm font-semibold text-[#161848] dark:text-white">Sin comentarios aún</h3>
                                                            <p class="text-sm text-gray-500 dark:text-gray-400">Inicie la conversación agregando una nota abajo.</p>
                                                        </div>
                                                    ');
                                                }

                                                // CONSTRUCCIÓN DEL CHAT
                                                $html = '<div class="space-y-4 max-h-[500px] overflow-y-auto pr-2 custom-scrollbar">';
                                                
                                                foreach ($comments as $comment) {
                                                    $user = $comment->user;
                                                    $userName = $user ? $user->name : 'Sistema';
                                                    // Obtener iniciales
                                                    $initials = collect(explode(' ', $userName))->map(fn($w) => strtoupper(substr($w, 0, 1)))->take(2)->implode('');
                                                    $date = $comment->created_at->format('d M Y, h:i A');
                                                    $isCurrentUser = $user && $user->id === auth()->id();
                                                                                                        
                                                    $html .= '
                                                    <div class="flex items-start gap-3 group">
                                                        <div class="flex-shrink-0">
                                                            <div class="flex items-center justify-center w-10 h-10 rounded-full bg-[#161848] text-white text-xs font-bold shadow-md ring-2 ring-white dark:ring-gray-800">
                                                                '.$initials.'
                                                            </div>
                                                        </div>
                                                        <div class="flex-1 min-w-0">
                                                            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg rounded-tl-none shadow-sm p-4 hover:shadow-md transition-shadow duration-200 relative">
                                                                
                                                                <div class="absolute left-0 top-2 bottom-2 w-1 bg-[#26cad3] rounded-r"></div>

                                                                <div class="flex items-center justify-between mb-1 ml-2">
                                                                    <h4 class="text-sm font-bold text-[#161848] dark:text-[#26cad3]">
                                                                        '.$userName.'
                                                                    </h4>
                                                                    <span class="text-xs text-gray-400 font-medium flex items-center gap-1">
                                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                                        '.$date.'
                                                                    </span>
                                                                </div>
                                                                <p class="text-sm text-gray-600 dark:text-gray-300 ml-2 whitespace-pre-wrap leading-relaxed">'.$comment->comment.'</p>
                                                            </div>
                                                        </div>
                                                    </div>';
                                                }
                                                
                                                $html .= '</div>';

                                                return new \Illuminate\Support\HtmlString($html);
                                            }),

                                        // === ÁREA DE TEXTO MEJORADA ===
                                        Forms\Components\Group::make()
                                            ->schema([
                                                Forms\Components\Textarea::make('new_comment_content') // Cambié el nombre para no chocar
                                                    ->label('Escribir nuevo comentario')
                                                    ->placeholder('Escriba aquí los detalles, observaciones o seguimiento...')
                                                    ->rows(3)
                                                    //->required()
                                                    // Estilo extra para que el textarea se vea integrado
                                                    ->extraInputAttributes(['class' => 'resize-none border-gray-300 focus:border-[#26cad3] focus:ring-[#26cad3]']),
                                                    
                                                Forms\Components\Actions::make([
                                                    Forms\Components\Actions\Action::make('guardar_comentario')
                                                        ->label('Publicar Comentario')
                                                        ->color('primary')
                                                        ->icon('heroicon-m-paper-airplane') // Icono de enviar
                                                        ->action(function (Forms\Get $get, Forms\Set $set) {
                                                            $content = $get('new_comment_content');
                                                            if (!$content) return;

                                                            RentComment::create([
                                                                'rent_id' => $this->record->id,
                                                                'user_id' => auth()->id(),
                                                                'comment' => $content,
                                                                'status' => 'activo',
                                                            ]);

                                                            // Limpiar el campo y notificar
                                                            $set('new_comment_content', '');
                                                            \Filament\Notifications\Notification::make()
                                                                ->success()
                                                                ->title('Comentario registrado')
                                                                ->body('Se ha agregado la nota al historial.')
                                                                ->send();
                                                        }),
                                                ])->alignRight(), // Botón a la derecha
                                            ])
                                            ->columnSpanFull()
                                            ->extraAttributes(['class' => 'bg-gray-50 dark:bg-white/5 p-4 rounded-xl border border-gray-200 dark:border-gray-700 mt-4']),
                                    ]),
                            ]),
                    ]),
            ]);
    }
}
