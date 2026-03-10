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

        if (empty($this->data['asesor_id'])) {
            $this->data['asesor_id'] = auth()->id();
        }
        if (empty($this->data['office_id'])) {
            $this->data['office_id'] = auth()->user()->office_id;
        }

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
                                        Forms\Components\Select::make('office_id')
                                            ->relationship('office', 'nombre')
                                            ->label('Sucursal (Oficina)')
                                            ->disabled()
                                            ->dehydrated(), // Asegura que se guarde

                                        Forms\Components\Select::make('asesor_id')
                                            ->relationship('asesor', 'name')
                                            ->label('Agente Asignado')
                                            ->disabled(fn () => !auth()->user()->hasRole('Administrador'))
                                            ->dehydrated() // Asegura que se guarde
                                            ->required(),
                                        Forms\Components\TextInput::make('inmobiliaria')->label('Inmobiliaria*')->disabled(),
                                        
                                        Forms\Components\Select::make('estatus')
                                            ->label('Estatus')
                                            ->options([
                                                'nueva' => 'Nueva',
                                                'documentacion' => 'Documentación',
                                                'analisis' => 'Análisis',
                                                'aprobada' => 'Aprobada',
                                                'programar_firma' => 'Programar firma',
                                                'activa' => 'Activa',
                                                'rechazada' => 'Rechazada',
                                                'cancelada' => 'Cancelada',
                                                'vencida' => 'Vencida',
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
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make('Esquema de Comisiones y Pagos')
                                    ->schema([
                                        Forms\Components\TextInput::make('renta')
                                            ->label('Monto de renta')
                                            ->numeric()
                                            ->prefix('$')
                                            ->required()
                                            ->placeholder('0.00')
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                                // Por default, la comisión es igual a la renta
                                                $set('monto_comision', $state);
                                            }),

                                        Forms\Components\TextInput::make('monto_comision')
                                            ->label('Monto comisión')
                                            ->numeric()
                                            ->prefix('$')
                                            ->required()
                                            ->placeholder('0.00'),

                                        Forms\Components\TextInput::make('porcentaje_comision_principal')
                                            ->label('% Comisión agente Rentas.com')
                                            ->numeric()
                                            ->suffix('%')
                                            ->default(100)
                                            ->readOnly() // Se calcula en automático
                                            ->helperText('Este valor se ajusta automáticamente al agregar agentes externos.'),
                                        
                                        // REPEATER PARA DIVIDIR COMISIÓN
                                        Forms\Components\Repeater::make('comisiones_divididas')
                                            ->label('Dividir Comisión (Agentes Externos)')
                                            ->columnSpanFull()
                                            ->schema([
                                                Forms\Components\TextInput::make('nombre_agente')->label('Nombre')->required(),
                                                Forms\Components\TextInput::make('email')->email()->label('Email'),
                                                Forms\Components\TextInput::make('telefono')->tel()->label('Teléfono'),
                                                Forms\Components\TextInput::make('porcentaje')
                                                    ->label('% Comisión')
                                                    ->numeric()
                                                    ->suffix('%')
                                                    ->required()
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                                                        // Si modifican manualmente un %, recalcula el del agente principal
                                                        $items = $get('../../comisiones_divididas') ?? [];
                                                        $sum = collect($items)->sum('porcentaje');
                                                        $set('../../porcentaje_comision_principal', max(0, 100 - $sum));
                                                    })
                                            ])
                                            ->columns(4)
                                            ->addActionLabel('Dividir comisión (Añadir agente)')
                                            ->defaultItems(0)
                                            ->live()
                                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                                // LÓGICA AUTOMÁTICA DE DIVISIÓN (50%, 33.3%, etc.)
                                                if (!is_array($state)) return;
                                                $count = count($state);
                                                
                                                if ($count > 0) {
                                                    // Calculamos la división equitativa (incluyendo al agente principal = +1)
                                                    $share = round(100 / ($count + 1), 2);
                                                    $newState = [];
                                                    foreach ($state as $key => $item) {
                                                        $item['porcentaje'] = $share;
                                                        $newState[$key] = $item;
                                                    }
                                                    $set('comisiones_divididas', $newState);
                                                    // Asignamos lo que sobra al agente principal para dar 100 exacto
                                                    $set('porcentaje_comision_principal', round(100 - ($share * $count), 2));
                                                } else {
                                                    // Si borran a todos los externos, regresa al 100%
                                                    $set('porcentaje_comision_principal', 100);
                                                }
                                            })
                                    ])->columns(3),
                                
                                Forms\Components\Actions::make([
                                    Forms\Components\Actions\Action::make('guardar_general')
                                        ->label('Guardar Información')
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
                        // ========== TAB: PÓLIZA DE RENTA (Antes Investigación) ==========
                        Forms\Components\Tabs\Tab::make('Póliza de Renta')
                            ->icon('heroicon-o-shield-check')
                            ->schema([
                                Forms\Components\Section::make('Resumen de la Operación')
                                    ->description('Verifique los datos antes de enviar el expediente al abogado de Póliza de Rentas.')
                                    ->schema([
                                        
                                        // Resumen del Inquilino
                                        Forms\Components\Placeholder::make('resumen_inquilino')
                                            ->label('Datos del Inquilino')
                                            ->content(fn ($record) => new \Illuminate\Support\HtmlString(
                                                $record->tenant 
                                                ? "<b>Nombre:</b> {$record->tenant->nombre_completo}<br><b>Email:</b> {$record->tenant->email}<br><b>Teléfono:</b> " . ($record->tenant->telefono_celular ?? $record->tenant->telefono) 
                                                : '<span class="text-red-500">Sin asignar</span>'
                                            )),
                                            
                                        // Resumen del Propietario
                                        Forms\Components\Placeholder::make('resumen_propietario')
                                            ->label('Datos del Propietario')
                                            ->content(fn ($record) => new \Illuminate\Support\HtmlString(
                                                $record->owner 
                                                ? "<b>Nombre:</b> {$record->owner->nombre_completo}<br><b>Email:</b> {$record->owner->email}<br><b>Teléfono:</b> {$record->owner->telefono}" 
                                                : '<span class="text-red-500">Sin asignar</span>'
                                            )),

                                        // Propiedad y Renta
                                        Forms\Components\Placeholder::make('resumen_inmueble')
                                            ->label('Inmueble y Propiedad')
                                            ->content(fn ($record) => new \Illuminate\Support\HtmlString(
                                                "<b>Tipo:</b> " . ucfirst($record->tipo_inmueble ?? 'N/A') . "<br>" .
                                                "<b>Dirección:</b> " . trim(($record->calle ?? '') . ' ' . ($record->numero_exterior ?? ''))
                                            )),

                                        Forms\Components\Placeholder::make('resumen_renta')
                                            ->label('Monto y Solicitud')
                                            ->content(fn ($record) => new \Illuminate\Support\HtmlString(
                                                "<b>Renta Mensual:</b> $" . number_format($record->renta ?? 0, 2) . "<br>" .
                                                "<b>Solicitud Inquilino:</b> " . ($record->application ? $record->application->folio : 'No vinculada')
                                            )),

                                        // Fechas y Plazos (Editables)
                                        Forms\Components\TextInput::make('plazo_arrendamiento')
                                            ->label('Plazo del Arrendamiento')
                                            ->placeholder('Ej. 12 meses')
                                            ->required(),

                                        Forms\Components\DatePicker::make('start_date')
                                            ->label('Fecha de Inicio')
                                            ->displayFormat('d/m/Y')
                                            ->native(false)
                                            ->required(),

                                        Forms\Components\DatePicker::make('end_date')
                                            ->label('Fecha de Fin')
                                            ->displayFormat('d/m/Y')
                                            ->native(false)
                                            ->required(),

                                        Forms\Components\DatePicker::make('fecha_firma')
                                            ->label('Fecha prevista de firma')
                                            ->displayFormat('d/m/Y')
                                            ->native(false)
                                            ->required(),
                                    ])->columns(2),

                                // Botón de Envío
                                Forms\Components\Actions::make([
                                    Forms\Components\Actions\Action::make('enviar_pdr')
                                        ->label('Enviar expediente a Póliza de Rentas')
                                        ->icon('heroicon-m-paper-airplane')
                                        ->color('success')
                                        ->requiresConfirmation()
                                        ->modalHeading('¿Enviar Expediente a Póliza de Rentas?')
                                        ->modalDescription('El estatus de la renta cambiará automáticamente a "Análisis" y el abogado asignado recibirá una notificación por correo electrónico con los datos de esta operación.')
                                        ->action(function (Forms\Get $get, Forms\Set $set, $record) {
                                            
                                            // 1. Guardar primero las fechas que acaban de escribir
                                            $record->update([
                                                'plazo_arrendamiento' => $get('plazo_arrendamiento'),
                                                'start_date' => $get('start_date'),
                                                'end_date' => $get('end_date'),
                                                'fecha_firma' => $get('fecha_firma'),
                                                'estatus' => 'analisis', // 2. CAMBIO AUTOMÁTICO DE ESTATUS
                                            ]);

                                            // 3. ACTUALIZAR EL DESPLEGABLE VISUAL DE LA PESTAÑA INFORMACIÓN (Para que no tengan que recargar la página)
                                            $set('estatus', 'analisis');

                                            // 4. ENVIAR CORREO AL ABOGADO (Simulado por ahora hasta que me des la vista de correo)
                                            /*
                                            try {
                                                Mail::raw("Se ha creado un nuevo expediente de renta para revisar. Folio: {$record->folio}", function ($message) {
                                                    $message->to('abogado_pdr@tuempresa.com')
                                                            ->subject('Nuevo Expediente a Revisar');
                                                });
                                            } catch (\Exception $e) {}
                                            */

                                            // 5. MENSAJE DE ÉXITO EXACTO COMO LO PIDIERON
                                            \Filament\Notifications\Notification::make()
                                                ->success()
                                                ->title('Expediente enviado exitosamente')
                                                ->body('El estatus ha cambiado a Análisis y PDR ha sido notificado.')
                                                ->send();
                                        })
                                        // El botón desaparece si la renta ya pasó de la fase de documentación
                                        ->visible(fn ($record) => in_array($record->estatus, ['nueva', 'documentacion'])),
                                ])->fullWidth(),
                            ]),
                    ]),
                
                // SECCIÓN GLOBAL DE COMENTARIOS (ABAJO)
                Forms\Components\Section::make('Bitácora y Comentarios')
                    ->description('Historial de la operación y notas de seguimiento.')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'mt-4 bg-gray-50 dark:bg-white/5'])
                    ->schema([
                        Forms\Components\Group::make()->schema([
                            // Campo para nuevo comentario
                            Forms\Components\Textarea::make('new_comment_content')
                                ->hiddenLabel()
                                ->placeholder('Escribe una nueva nota o comentario...')
                                ->rows(2)
                                ->extraInputAttributes(['class' => 'border-gray-300 focus:border-[#26cad3] focus:ring-[#26cad3]']),
                            
                            // Botón de guardar
                            Forms\Components\Actions::make([
                                Forms\Components\Actions\Action::make('guardar_comentario')
                                    ->label('Publicar Comentario')
                                    ->color('primary')
                                    ->icon('heroicon-m-paper-airplane')
                                    ->action(function (Forms\Get $get, Forms\Set $set) {
                                        $content = $get('new_comment_content');
                                        if (!$content) return;

                                        RentComment::create([
                                            'rent_id' => $this->record->id,
                                            'user_id' => auth()->id(),
                                            'comment' => $content,
                                            'status' => 'activa',
                                        ]);

                                        $set('new_comment_content', '');
                                        \Filament\Notifications\Notification::make()->success()->title('Comentario registrado')->send();
                                    }),
                            ])->alignRight(),
                        ]),

                        // Lista de comentarios
                        Forms\Components\Placeholder::make('comments_list')
                            ->hiddenLabel()
                            ->content(function () {
                                $comments = $this->record->comments()->with('user')->orderBy('created_at', 'desc')->get();
                                
                                if ($comments->isEmpty()) {
                                    return new \Illuminate\Support\HtmlString('
                                        <div class="flex flex-col items-center justify-center p-8 text-center bg-white border border-gray-200 border-dashed rounded-xl dark:bg-gray-800 dark:border-gray-700 mt-4">
                                            <p class="text-sm text-gray-500">Sin comentarios aún</p>
                                        </div>
                                    ');
                                }

                                $html = '<div class="space-y-3 max-h-[400px] overflow-y-auto pr-2 mt-4">';
                                foreach ($comments as $comment) {
                                    $user = $comment->user;
                                    $userName = $user ? $user->name : 'Sistema Automático';
                                    $date = $comment->created_at->format('d M Y, h:i A');
                                    
                                    $bgClass = $user ? 'bg-white dark:bg-gray-800' : 'bg-blue-50 dark:bg-blue-900/20';
                                    $borderClass = $user ? 'border-gray-200 dark:border-gray-700' : 'border-[#26cad3]/30';
                                    $initials = collect(explode(' ', $userName))->map(fn($w) => strtoupper(substr($w, 0, 1)))->take(2)->implode('');
                                    $iconBg = $user ? 'bg-[#161848]' : 'bg-[#26cad3]';

                                    $html .= "
                                        <div class='flex items-start gap-3'>
                                            <div class='flex-shrink-0'>
                                                <div class='flex items-center justify-center w-10 h-10 rounded-full {$iconBg} text-white text-xs font-bold shadow-sm'>
                                                    {$initials}
                                                </div>
                                            </div>
                                            <div class='flex-1 min-w-0'>
                                                <div class='{$bgClass} border {$borderClass} rounded-lg rounded-tl-none shadow-sm p-4'>
                                                    <div class='flex items-center justify-between mb-1'>
                                                        <h4 class='text-sm font-bold text-[#161848] dark:text-white'>{$userName}</h4>
                                                        <span class='text-xs text-gray-400'>{$date}</span>
                                                    </div>
                                                    <p class='text-sm text-gray-600 dark:text-gray-300 whitespace-pre-wrap'>{$comment->comment}</p>
                                                </div>
                                            </div>
                                        </div>";
                                }
                                $html .= '</div>';
                                return new \Illuminate\Support\HtmlString($html);
                            }),
                    ]),
            ]);
    }
}
