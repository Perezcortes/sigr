<?php

namespace App\Filament\Resources\RentResource\Pages;

use App\Filament\Resources\ApplicationsResource;
use App\Filament\Resources\GuarantorRequestResource;
use App\Filament\Resources\OwnerRequestResource;
use App\Filament\Resources\RentResource;
use App\Filament\Resources\TenantRequestResource;
use App\Services\PdrApi\PdrApiService;
use App\Models\Application;
use App\Models\GuarantorDocument;
use App\Models\GuarantorRequest;
use App\Models\Owner;
use App\Models\OwnerDocument;
use App\Models\OwnerRequest;
use App\Models\Property;
use App\Models\PropertyDocument;
use App\Models\Rent;
use App\Models\RentComment;
use App\Models\TenantDocument;
use App\Models\TenantRequest;
use App\Models\User;
use App\Support\Filament\AdministrationTabs;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;

class ViewRent extends EditRecord
{
    protected static string $resource = RentResource::class;

    protected static ?string $title = 'Ver Renta';

    protected static string $view = 'filament.resources.rent-resource.pages.view-rent';

    protected bool $advancedToDocumentacionThisRequest = false;

    public ?array $tenantData = [];

    public ?array $ownerData = [];

    public function resolveRecord(string|int $key): Model
    {
        $record = Rent::findByHash($key);
        if (! $record && is_numeric($key)) {
            $record = Rent::find($key);
        }
        if (! $record) {
            abort(404);
        }

        return $record;
    }

    public function mount($record): void
    {
        parent::mount($record);

        $user = auth()->user();

        if (empty($this->data['asesor_id']) && ! $user->hasRole('Administrador')) {
            $this->data['asesor_id'] = $user->id;
        }

        if (empty($this->data['office_id']) && filled($user->office_id)) {
            $this->data['office_id'] = $user->office_id;
        }

        // Extraemos el Inquilino
        $tenant = $this->record->tenant?->fresh();

        if ($tenant) {
            $this->data['tenant_tipo_persona'] = $tenant->tipo_persona;
            $this->data['tenant_nombres'] = $tenant->nombres;
            $this->data['tenant_primer_apellido'] = $tenant->primer_apellido;
            $this->data['tenant_segundo_apellido'] = $tenant->segundo_apellido;
            $this->data['tenant_sexo'] = $tenant->sexo;
            $this->data['tenant_razon_social'] = $tenant->razon_social;
            $this->data['tenant_rfc'] = $tenant->rfc;
            $this->data['tenant_email'] = $tenant->email;
        }

        // extraemos el Propietario
        $owner = $this->record->owner?->fresh();

        if ($owner) {
            $this->data['owner_tipo_persona'] = $owner->tipo_persona;
            $this->data['owner_nombres'] = $owner->nombres;
            $this->data['owner_primer_apellido'] = $owner->primer_apellido;
            $this->data['owner_segundo_apellido'] = $owner->segundo_apellido;
            $this->data['owner_sexo'] = $owner->sexo;
            $this->data['owner_razon_social'] = $owner->razon_social;
            $this->data['owner_rfc'] = $owner->rfc;
            $this->data['owner_email'] = $owner->email;
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

        $this->applyDefaultResumenProcesoRentas();
    }

    /**
     * Valores de plazo (meses) permitidos en «Proceso de Renta».
     *
     * @return list<int>
     */
    protected static function plazoArrendamientoMesesPermitidos(): array
    {
        return [3, 6, 12, 18, 24];
    }

    /**
     * Normaliza el plazo guardado (p. ej. "12 meses") a la clave del select ("12").
     */
    protected static function normalizarPlazoArrendamientoAMeses(?string $valor): ?string
    {
        if (blank($valor)) {
            return null;
        }
        $meses = (int) preg_replace('/\D/', '', (string) $valor);

        return in_array($meses, self::plazoArrendamientoMesesPermitidos(), true)
            ? (string) $meses
            : null;
    }

    protected function syncResumenEndDate(Forms\Get $get, Forms\Set $set): void
    {
        $meses = (int) ($get('plazo_arrendamiento') ?? 0);
        $inicio = $get('start_date');
        if (! in_array($meses, self::plazoArrendamientoMesesPermitidos(), true) || blank($inicio)) {
            return;
        }
        try {
            $fin = Carbon::parse($inicio)->addMonths($meses)->subDay()->format('Y-m-d');
            $set('end_date', $fin);
        } catch (\Throwable) {
            // ignorar fechas inválidas momentáneas
        }
    }

    protected function applyDefaultResumenProcesoRentas(): void
    {
        if (blank($this->data['start_date'] ?? null) && blank($this->record->start_date)) {
            $this->data['start_date'] = now()->toDateString();
        }

        if (blank($this->data['fecha_firma'] ?? null) && blank($this->record->fecha_firma)) {
            $this->data['fecha_firma'] = now()->toDateString();
        }

        $clavePlazo = self::normalizarPlazoArrendamientoAMeses(
            (string) ($this->data['plazo_arrendamiento'] ?? $this->record->plazo_arrendamiento ?? '')
        );
        if ($clavePlazo !== null) {
            $this->data['plazo_arrendamiento'] = $clavePlazo;
        }

        if (blank($this->data['plazo_arrendamiento'] ?? null)) {
            $this->data['plazo_arrendamiento'] = '12';
        }

        $meses = (int) $this->data['plazo_arrendamiento'];
        $inicio = $this->data['start_date'] ?? null;
        if (in_array($meses, self::plazoArrendamientoMesesPermitidos(), true) && filled($inicio)) {
            try {
                $this->data['end_date'] = Carbon::parse($inicio)->addMonths($meses)->subDay()->format('Y-m-d');
            } catch (\Throwable) {
                // ignorar
            }
        }
    }

    protected function afterSave(): void
    {
        $this->record->refresh();

        if ($this->record->estatus !== 'nueva') {
            return;
        }

        $this->record->update(['estatus' => 'documentacion']);
        $this->record->refresh();
        $this->data['estatus'] = 'documentacion';
        $this->advancedToDocumentacionThisRequest = true;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data = parent::mutateFormDataBeforeSave($data);

        $principal = (float) ($data['porcentaje_comision_principal'] ?? 0);
        $items = $data['comisiones_divididas'] ?? [];
        if (! is_array($items)) {
            $items = [];
        }
        $sumOthers = collect($items)->sum(fn ($row) => (float) ($row['porcentaje'] ?? 0));
        $total = $principal + $sumOthers;

        if (abs($total - 100) > 0.02) {
            throw ValidationException::withMessages([
                'data.porcentaje_comision_principal' => 'Los porcentajes del agente principal y de los colaboradores deben sumar 100% (suma actual: '.number_format($total, 2).'%).',
            ]);
        }

        return $data;
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
        Notification::make()->success()->title('Documento eliminado')->send();
    }

    public function deleteGuarantorDocument(int $id): void
    {
        GuarantorDocument::find($id)?->delete();
        Notification::make()->success()->title('Documento eliminado')->send();
    }

    public function deleteOwnerDocument(int $id): void
    {
        OwnerDocument::find($id)?->delete();
        Notification::make()->success()->title('Documento eliminado')->send();
    }

    public function deletePropertyDocument(int $id): void
    {
        PropertyDocument::find($id)?->delete();
        Notification::make()->success()->title('Documento eliminado')->send();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Tabs')
                    ->persistTabInQueryString('tab')
                    ->columnSpanFull()
                    ->tabs([
                        // ========== TAB: INFORMACIÓN ==========
                        Forms\Components\Tabs\Tab::make('Información')
                            ->id('informacion')
                            ->icon('heroicon-o-information-circle')
                            ->schema([

                                Forms\Components\Section::make('Datos de la renta')
                                    ->schema([
                                        Forms\Components\TextInput::make('folio')
                                            ->label('Folio')
                                            ->disabled(),
                                        Forms\Components\Select::make('pdr_office_id')
                                            ->label('Equipo (PDR)')
                                            ->options(function (PdrApiService $api) {
                                                return $api->obtenerSucursales();
                                            })
                                            ->disabled(fn () => ! auth()->user()->hasRole('Administrador'))
                                            ->searchable()
                                            ->preload()
                                            ->live()
                                            ->required(fn () => auth()->user()->hasRole('Administrador'))
                                            ->helperText(fn () => auth()->user()->hasRole('Administrador')
                                                ? 'Elige el equipo para cargar los agentes desde Póliza de Rentas.'
                                                : null)
                                            ->afterStateUpdated(function (Forms\Set $set) {
                                                if (! auth()->user()->hasRole('Administrador')) {
                                                    return;
                                                }
                                                $set('pdr_asesor_id', null); // Limpiamos el asesor si cambia el equipo
                                            }),

                                        Forms\Components\Select::make('pdr_asesor_id')
                                            ->label('Agente (PDR)')
                                            ->options(function (Forms\Get $get, PdrApiService $api) {
                                                $officeHash = $get('pdr_office_id');

                                                if (blank($officeHash)) {
                                                    return [];
                                                }

                                                $agentes = $api->obtenerAgentesPorSucursal($officeHash);
                                                
                                                return $agentes;
                                            })
                                            ->disabled(fn () => ! auth()->user()->hasRole('Administrador'))
                                            ->searchable()
                                            ->preload()
                                            ->placeholder(fn (Forms\Get $get) => auth()->user()->hasRole('Administrador') && blank($get('pdr_office_id'))
                                                ? 'Primero selecciona un equipo'
                                                : 'Selecciona un agente')
                                            ->required(),

                                        Forms\Components\Select::make('estatus')
                                            ->label('Estatus')
                                            ->options(function (): array {
                                                $options = [
                                                    'nueva' => 'Nueva',
                                                    'documentacion' => 'Documentación',
                                                    'analisis' => 'Análisis',
                                                    'activa' => 'Activa',
                                                    'cancelada' => 'Cancelada',
                                                    'vencida' => 'Vencida',
                                                ];
                                                $current = $this->record->estatus ?? null;
                                                $legacyLabels = [
                                                    'aprobada' => 'Aprobada',
                                                    'programar_firma' => 'Programar firma',
                                                    'rechazada' => 'Rechazada',
                                                ];
                                                if ($current && isset($legacyLabels[$current])) {
                                                    return [$current => $legacyLabels[$current]] + $options;
                                                }

                                                return $options;
                                            })
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
                                        Forms\Components\Grid::make(3)
                                            ->schema([

                                                Forms\Components\Group::make()
                                                    ->schema([
                                                        Forms\Components\Grid::make(2)->schema([
                                                            Forms\Components\TextInput::make('renta')
                                                                ->label('Monto de renta')
                                                                ->numeric()
                                                                ->prefix('$')
                                                                ->required()
                                                                ->placeholder('0.00')
                                                                ->live(onBlur: true)
                                                                ->afterStateUpdated(function (Forms\Set $set, $state) {
                                                                    $set('monto_comision', $state);
                                                                }),

                                                            Forms\Components\TextInput::make('monto_comision')
                                                                ->label('Monto total de la comisión')
                                                                ->numeric()
                                                                ->prefix('$')
                                                                ->required()
                                                                ->live(onBlur: true)
                                                                ->placeholder('0.00'),
                                                        ]),

                                                        Forms\Components\TextInput::make('porcentaje_comision_principal')
                                                            ->label('% Comisión para mí (Agente Principal)')
                                                            ->numeric()
                                                            ->suffix('%')
                                                            ->default(100)
                                                            ->minValue(0)
                                                            ->maxValue(100)
                                                            ->live(onBlur: true)
                                                            ->helperText('Editable. La suma de este porcentaje más el de cada colaborador debe ser 100%.'),

                                                        Forms\Components\Actions::make([
                                                            Action::make('add_comision_externo')
                                                                ->label('Colaborador externo')
                                                                ->icon('heroicon-o-user-plus')
                                                                ->color('gray')
                                                                ->modalHeading('Añadir colaborador externo')
                                                                ->modalSubmitActionLabel('Agregar')
                                                                ->form([
                                                                    Forms\Components\TextInput::make('nombre_agente')
                                                                        ->label('Nombre')
                                                                        ->required()
                                                                        ->maxLength(255),
                                                                    Forms\Components\TextInput::make('email')
                                                                        ->label('Correo')
                                                                        ->email()
                                                                        ->required()
                                                                        ->maxLength(255),
                                                                    Forms\Components\TextInput::make('telefono')
                                                                        ->label('Teléfono')
                                                                        ->tel()
                                                                        ->required()
                                                                        ->maxLength(32),
                                                                ])
                                                                ->action(function (array $data, Forms\Get $get, Forms\Set $set): void {
                                                                    $rows = $get('comisiones_divididas') ?? [];
                                                                    if (! is_array($rows)) {
                                                                        $rows = [];
                                                                    }
                                                                    $rows[] = [
                                                                        'tipo' => 'externo',
                                                                        'nombre_agente' => $data['nombre_agente'],
                                                                        'email' => $data['email'],
                                                                        'telefono' => $data['telefono'],
                                                                        'porcentaje' => 0,
                                                                    ];
                                                                    $set('comisiones_divididas', array_values($rows));
                                                                }),

                                                            Action::make('add_comision_interno')
                                                                ->label('Colaborador del equipo')
                                                                ->icon('heroicon-o-users')
                                                                ->color('primary')
                                                                ->modalHeading('Añadir colaborador del equipo')
                                                                ->modalSubmitActionLabel('Agregar')
                                                                ->disabled(fn (Forms\Get $get): bool => blank($get('office_id')))
                                                                ->tooltip(fn (Forms\Get $get): ?string => blank($get('office_id'))
                                                                    ? 'Primero elige el equipo en «Datos de la renta» para listar agentes de esa oficina.'
                                                                    : null)
                                                                ->form([
                                                                    Forms\Components\Select::make('user_id')
                                                                        ->label('Agente del equipo')
                                                                        ->searchable()
                                                                        ->required()
                                                                        ->options(function (Forms\Get $get): array {
                                                                            $officeId = $get('office_id');
                                                                            if (! filled($officeId)) {
                                                                                return [];
                                                                            }

                                                                            return User::query()
                                                                                ->where('office_id', $officeId)
                                                                                ->whereHas('roles', fn ($q) => $q->whereIn('name', ['Agente', 'Gerente']))
                                                                                ->orderBy('name')
                                                                                ->pluck('name', 'id')
                                                                                ->all();
                                                                        }),
                                                                ])
                                                                ->action(function (array $data, Forms\Get $get, Forms\Set $set): void {
                                                                    $rows = $get('comisiones_divididas') ?? [];
                                                                    if (! is_array($rows)) {
                                                                        $rows = [];
                                                                    }
                                                                    $user = User::find($data['user_id']);
                                                                    $rows[] = [
                                                                        'tipo' => 'interno',
                                                                        'user_id' => (int) $data['user_id'],
                                                                        'nombre_agente' => $user?->name,
                                                                        'email' => null,
                                                                        'telefono' => null,
                                                                        'porcentaje' => 0,
                                                                    ];
                                                                    $set('comisiones_divididas', array_values($rows));
                                                                }),

                                                            Action::make('repartir_comisiones_equilibrado')
                                                                ->label('Repartir por partes iguales')
                                                                ->icon('heroicon-o-scale')
                                                                ->color('success')
                                                                ->requiresConfirmation()
                                                                ->modalHeading('Repartir por partes iguales')
                                                                ->modalDescription('Se asignará el mismo porcentaje a cada colaborador y el remanente al agente principal para totalizar 100%.')
                                                                ->action(function (Forms\Get $get, Forms\Set $set): void {
                                                                    $items = $get('comisiones_divididas') ?? [];
                                                                    if (! is_array($items)) {
                                                                        $items = [];
                                                                    }
                                                                    $count = count($items);
                                                                    if ($count === 0) {
                                                                        $set('porcentaje_comision_principal', 100);

                                                                        return;
                                                                    }
                                                                    $share = round(100 / ($count + 1), 2);
                                                                    $newState = [];
                                                                    foreach ($items as $key => $item) {
                                                                        if (! is_array($item)) {
                                                                            continue;
                                                                        }
                                                                        $item['porcentaje'] = $share;
                                                                        $newState[$key] = $item;
                                                                    }
                                                                    $set('comisiones_divididas', array_values($newState));
                                                                    $set('porcentaje_comision_principal', round(100 - ($share * $count), 2));
                                                                }),
                                                        ])
                                                            ->columns(3),

                                                        Forms\Components\Repeater::make('comisiones_divididas')
                                                            ->label('Colaboradores en la comisión')
                                                            ->schema([
                                                                Forms\Components\Select::make('tipo')
                                                                    ->label('Tipo')
                                                                    ->options([
                                                                        'externo' => 'Externo',
                                                                        'interno' => 'Interno',
                                                                    ])
                                                                    ->default('externo')
                                                                    ->disabled()
                                                                    ->dehydrated(),

                                                                Forms\Components\TextInput::make('nombre_agente')
                                                                    ->label('Nombre')
                                                                    ->maxLength(255)
                                                                    ->visible(fn (Forms\Get $get) => ($get('tipo') ?: 'externo') === 'externo')
                                                                    ->required(fn (Forms\Get $get) => ($get('tipo') ?: 'externo') === 'externo'),

                                                                Forms\Components\TextInput::make('email')
                                                                    ->label('Correo')
                                                                    ->email()
                                                                    ->maxLength(255)
                                                                    ->visible(fn (Forms\Get $get) => ($get('tipo') ?: 'externo') === 'externo'),

                                                                Forms\Components\TextInput::make('telefono')
                                                                    ->label('Teléfono')
                                                                    ->tel()
                                                                    ->maxLength(32)
                                                                    ->visible(fn (Forms\Get $get) => ($get('tipo') ?: 'externo') === 'externo'),

                                                                Forms\Components\Select::make('user_id')
                                                                    ->label('Agente del equipo')
                                                                    ->searchable()
                                                                    ->visible(fn (Forms\Get $get) => ($get('tipo') ?: 'externo') === 'interno')
                                                                    ->required(fn (Forms\Get $get) => ($get('tipo') ?: 'externo') === 'interno')
                                                                    ->options(function (Forms\Get $get): array {
                                                                        $officeId = $get('../../office_id');
                                                                        if (! filled($officeId)) {
                                                                            return [];
                                                                        }

                                                                        return User::query()
                                                                            ->where('office_id', $officeId)
                                                                            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['Agente', 'Gerente']))
                                                                            ->orderBy('name')
                                                                            ->pluck('name', 'id')
                                                                            ->all();
                                                                    })
                                                                    ->live(onBlur: true)
                                                                    ->afterStateUpdated(function (Forms\Set $set, $state): void {
                                                                        if (filled($state)) {
                                                                            $set('nombre_agente', User::find($state)?->name ?? '');
                                                                        }
                                                                    }),

                                                                Forms\Components\TextInput::make('porcentaje')
                                                                    ->label('% Comisión')
                                                                    ->numeric()
                                                                    ->suffix('%')
                                                                    ->default(0)
                                                                    ->required()
                                                                    ->minValue(0)
                                                                    ->maxValue(100)
                                                                    ->live(onBlur: true),
                                                            ])
                                                            ->columns(2)
                                                            ->addable(false)
                                                            ->reorderable(false)
                                                            ->defaultItems(0)
                                                            ->live(),
                                                    ])->columnSpan(2),

                                                Forms\Components\Section::make('Resumen de comisiones')
                                                    ->description('Distribución de la comisión')
                                                    ->schema([
                                                        Forms\Components\Placeholder::make('grafica_comisiones')
                                                            ->hiddenLabel()
                                                            ->content(function (Forms\Get $get) {
                                                                $montoTotal = (float) $get('monto_comision') ?: 0;
                                                                $pctPrincipal = (float) $get('porcentaje_comision_principal') ?: 0;
                                                                $filas = $get('comisiones_divididas') ?? [];
                                                                if (! is_array($filas)) {
                                                                    $filas = [];
                                                                }

                                                                $sumaOtros = collect($filas)->sum(fn ($row) => (float) ($row['porcentaje'] ?? 0));
                                                                $sumaTotal = $pctPrincipal + $sumaOtros;
                                                                $sumaOk = abs($sumaTotal - 100) <= 0.02;
                                                                $sumaClass = $sumaOk ? 'text-gray-600 dark:text-gray-400' : 'text-red-600 dark:text-red-400 font-semibold';

                                                                $nombrePrincipal = auth()->user()->name ?? 'Yo';
                                                                $montoPrincipal = ($montoTotal * $pctPrincipal) / 100;

                                                                $colores = ['bg-amber-500', 'bg-rose-500', 'bg-purple-500', 'bg-cyan-500'];
                                                                $colorIdx = 0;

                                                                $sumaBanner = "<p class='mb-3 text-sm {$sumaClass}'>Suma de porcentajes: ".number_format($sumaTotal, 2).'% (debe ser 100%)</p>';

                                                                $html = "<div class='w-full h-6 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden flex mb-6 shadow-inner'>";
                                                                $barPct = min(max($pctPrincipal, 0), 100);
                                                                $html .= "<div class='bg-primary-600 h-full transition-all duration-300' style='width: {$barPct}%' title='Mío: {$pctPrincipal}%'></div>";

                                                                $leyendaHtml = "<ul class='space-y-3 text-sm'>";
                                                                $leyendaHtml .= "<li class='flex items-center justify-between p-2 bg-primary-50 dark:bg-primary-900/20 rounded-lg border border-primary-100 dark:border-primary-800'>
                                                                    <span class='flex items-center gap-2'>
                                                                        <span class='w-3 h-3 rounded-full bg-primary-600 shadow-sm'></span>
                                                                        <strong class='text-primary-700 dark:text-primary-400'>{$nombrePrincipal} (Yo)</strong>
                                                                    </span>
                                                                    <div class='text-right'>
                                                                        <span class='text-xs text-gray-500 block'>".number_format($pctPrincipal, 1)."%</span>
                                                                        <strong class='text-base text-primary-700 dark:text-primary-400'>$".number_format($montoPrincipal, 2).'</strong>
                                                                    </div>
                                                                </li>';

                                                                foreach ($filas as $ext) {
                                                                    $tipo = $ext['tipo'] ?? 'externo';
                                                                    $pct = (float) ($ext['porcentaje'] ?? 0);
                                                                    if ($pct <= 0) {
                                                                        continue;
                                                                    }

                                                                    $montoExt = ($montoTotal * $pct) / 100;
                                                                    if ($tipo === 'interno' && ! empty($ext['user_id'])) {
                                                                        $nombreExt = User::find($ext['user_id'])?->name
                                                                            ?: ($ext['nombre_agente'] ?: 'Colaborador interno');
                                                                    } else {
                                                                        $nombreExt = $ext['nombre_agente'] ?: 'Colaborador externo';
                                                                    }
                                                                    $nombreExt = e($nombreExt);
                                                                    $colorClase = $colores[$colorIdx % count($colores)];

                                                                    $html .= "<div class='{$colorClase} h-full border-l border-white/20 transition-all duration-300' style='width: {$pct}%' title='{$nombreExt}: {$pct}%'></div>";

                                                                    $leyendaHtml .= "<li class='flex items-center justify-between p-2 hover:bg-gray-50 dark:hover:bg-white/5 rounded-lg transition-colors'>
                                                                        <span class='flex items-center gap-2'>
                                                                            <span class='w-3 h-3 rounded-full {$colorClase} shadow-sm'></span>
                                                                            <span class='text-gray-700 dark:text-gray-300 font-medium'>{$nombreExt}</span>
                                                                        </span>
                                                                        <div class='text-right'>
                                                                            <span class='text-xs text-gray-500 block'>".number_format($pct, 1)."%</span>
                                                                            <strong class='text-gray-900 dark:text-gray-100'>$".number_format($montoExt, 2).'</strong>
                                                                        </div>
                                                                    </li>';

                                                                    $colorIdx++;
                                                                }

                                                                $html .= '</div>';
                                                                $leyendaHtml .= '</ul>';

                                                                $totalHtml = "<div class='mt-6 pt-4 border-t border-gray-200 dark:border-gray-700 flex justify-between items-center'>
                                                                    <span class='text-gray-500 font-medium'>Total a repartir</span>
                                                                    <strong class='text-lg'>$".number_format($montoTotal, 2).'</strong>
                                                                </div>';

                                                                return new HtmlString($sumaBanner.$html.$leyendaHtml.$totalHtml);
                                                            }),
                                                    ])->columnSpan(1),
                                            ]),

                                        Forms\Components\Actions::make([
                                            Action::make('guardar_general')
                                                ->label('Guardar Información')
                                                ->color('primary')
                                                ->icon('heroicon-o-check')
                                                ->action(function () {
                                                    $this->advancedToDocumentacionThisRequest = false;
                                                    $this->save(shouldRedirect: false, shouldSendSavedNotification: false);

                                                    $notification = Notification::make()
                                                        ->success()
                                                        ->title('Datos guardados');

                                                    if ($this->advancedToDocumentacionThisRequest) {
                                                        $notification->body('El estatus pasó a Documentación. Ya puedes usar las pestañas Solicitudes, Documentos y Póliza de Renta.');
                                                    }

                                                    $notification->send();
                                                    $this->advancedToDocumentacionThisRequest = false;
                                                }),
                                            Action::make('cancelar')
                                                ->label('Cancelar')
                                                ->color('gray')
                                                ->icon('heroicon-o-x-mark')
                                                ->url(fn () => RentResource::getUrl('index')),
                                        ]),
                                    ]),
                            ]),

                        // ========== TAB: SOLICITUDES ==========
                        Forms\Components\Tabs\Tab::make('Solicitudes')
                            ->id('solicitudes')
                            ->icon('heroicon-o-clipboard-document-list')
                            ->visible(fn (): bool => $this->record->estatus !== 'nueva')
                            ->schema([
                                Forms\Components\Tabs::make('SolicitudesTabs')
                                    ->persistTabInQueryString('solicitud')
                                    ->columnSpanFull()
                                    ->tabs([
                                        // Sub-tab: Inquilino
                                        Forms\Components\Tabs\Tab::make('Inquilino')
                                            ->id('inquilino')
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
                                                                            $nombre = trim(($tenant->nombres ?? '').' '.($tenant->primer_apellido ?? '').' '.($tenant->segundo_apellido ?? ''));
                                                                        } else {
                                                                            $nombre = $tenant->razon_social ?? '';
                                                                        }
                                                                        $label = ($nombre ?: 'Sin nombre').' - '.($application->folio ?? 'N/A');
                                                                    } else {
                                                                        $label = ($application->user->name ?? 'Usuario').' - '.($application->folio ?? 'N/A');
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

                                                                        Notification::make()
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
                                                                $tenant = $this->record->tenant?->fresh();
                                                                if (! $tenant) {
                                                                    return 'No hay inquilino asignado';
                                                                }
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
                                                            ->required(fn (): bool => (bool) $this->record->tenant_id)
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
                                                    Action::make('actualizar_inquilino')
                                                        ->label('Guardar')
                                                        ->color('primary')
                                                        ->icon('heroicon-o-check')
                                                        ->action(function () {
                                                            if ($this->record->tenant) {

                                                                $cleanedData = collect($this->data)->map(fn($val) => $val === '' ? null : $val)->toArray();

                                                                $updateData = [
                                                                    'tipo_persona' => $cleanedData['tenant_tipo_persona'] ?? 'fisica',
                                                                    'email' => $cleanedData['tenant_email'] ?? null, 
                                                                ];

                                                                if (($cleanedData['tenant_tipo_persona'] ?? 'fisica') === 'fisica') {
                                                                    $updateData['nombres']          = $cleanedData['tenant_nombres'] ?? null;
                                                                    $updateData['primer_apellido']  = $cleanedData['tenant_primer_apellido'] ?? null;
                                                                    $updateData['segundo_apellido'] = $cleanedData['tenant_segundo_apellido'] ?? null;
                                                                    $updateData['sexo']             = $cleanedData['tenant_sexo'] ?? null; 
                                                                    $updateData['razon_social']     = null;
                                                                    $updateData['rfc']              = null;
                                                                } else {
                                                                    $updateData['razon_social']     = $cleanedData['tenant_razon_social'] ?? null;
                                                                    $updateData['rfc']              = $cleanedData['tenant_rfc'] ?? null;
                                                                    $updateData['nombres']          = null;
                                                                    $updateData['primer_apellido']  = null;
                                                                    $updateData['segundo_apellido'] = null;
                                                                    $updateData['sexo']             = null;
                                                                }

                                                                $this->record->tenant->update($updateData);

                                                                // Sincronizamos la solicitud (TenantRequest) si ya existe
                                                                $tenantRequest = TenantRequest::where('tenant_id', $this->record->tenant_id)
                                                                    ->where('rent_id', $this->record->id)
                                                                    ->first();

                                                                if ($tenantRequest) {
                                                                    $tenantRequest->update($updateData);
                                                                }

                                                                // Persistir application_id y actualizar tenant_id si está presente
                                                                if (isset($cleanedData['application_id'])) {
                                                                    $application = Application::with('user.tenant')->find($cleanedData['application_id']);
                                                                    if ($application && $application->user && $application->user->tenant) {
                                                                        $this->record->update([
                                                                            'application_id' => $cleanedData['application_id'],
                                                                            'tenant_id' => $application->user->tenant->id,
                                                                        ]);
                                                                    } else {
                                                                        $this->record->update(['application_id' => $cleanedData['application_id']]);
                                                                    }
                                                                }

                                                                Notification::make()->success()->title('Inquilino actualizado')->send();
                                                                $this->redirect(RentResource::getUrl('view', ['record' => $this->record]) . '?tab=-solicitudes-tab&solicitud=-inquilino-tab');
                                                            }
                                                        }),
                                                    Action::make('edit_tenant')
                                                        ->label('Editar solicitud del inquilino')
                                                        ->color('primary')
                                                        ->action(function () {
                                                            // Guardamos el tipo de póliza actual en la BD antes de irnos
                                                            if (isset($this->data['tipo_poliza'])) {
                                                                $this->record->update(['tipo_poliza' => $this->data['tipo_poliza']]);
                                                            }
                                                            // Si hay una Application vinculada, usar esa
                                                            if ($this->record->application_id) {
                                                                $this->redirect(ApplicationsResource::getUrl('edit', ['record' => $this->record->application_id]));

                                                                return;
                                                            }

                                                            // Si no hay Application, usar el flujo anterior con TenantRequest
                                                            $tenantRequest = TenantRequest::where('tenant_id', $this->record->tenant_id)
                                                                ->where('rent_id', $this->record->id)->first();
                                                            if (! $tenantRequest) {
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
                                                    Action::make('send_tenant')->label('Enviar solicitud al inquilino')->color('success'),
                                                    Action::make('copy_link_tenant')
                                                        ->label('Copiar link')
                                                        ->color('gray')
                                                        ->icon('heroicon-o-link')
                                                        ->visible(fn () => $this->record->tenant)
                                                        ->action(function (Action $action) {

                                                            // Nos aseguramos de que el expediente exista en la BD. Si no, lo creamos igual que en el botón Editar.
                                                            $tenantRequest = TenantRequest::firstOrCreate(
                                                                [
                                                                    'tenant_id' => $this->record->tenant_id,
                                                                    'rent_id' => $this->record->id,
                                                                ],
                                                                [
                                                                    'estatus' => 'nueva',
                                                                    'nombres' => $this->record->tenant->nombres,
                                                                    'primer_apellido' => $this->record->tenant->primer_apellido,
                                                                    'segundo_apellido' => $this->record->tenant->segundo_apellido,
                                                                    'email' => $this->record->tenant->email,
                                                                    'rfc' => $this->record->tenant->rfc,
                                                                ]
                                                            );

                                                            // Armamos la URL pública real
                                                            $urlPublica = route('solicitud.inquilino.publica', $tenantRequest->id);

                                                            // Inyectamos JS nativo con un "Fallback" que fuerza el copiado incluso sin HTTPS
                                                            $action->getLivewire()->js("
                                                                const texto = '{$urlPublica}';
                                                                
                                                                // Intento 1: API (Requiere HTTPS o Localhost seguro)
                                                                if (navigator.clipboard && window.isSecureContext) {
                                                                    navigator.clipboard.writeText(texto);
                                                                } else {
                                                                    // Intento 2: Fallback tradicional 
                                                                    let textArea = document.createElement('textarea');
                                                                    textArea.value = texto;
                                                                    textArea.style.position = 'fixed';
                                                                    textArea.style.opacity = '0';
                                                                    document.body.appendChild(textArea);
                                                                    textArea.focus();
                                                                    textArea.select();
                                                                    try {
                                                                        document.execCommand('copy');
                                                                    } catch (err) {
                                                                        console.error('No se pudo copiar', err);
                                                                    }
                                                                    document.body.removeChild(textArea);
                                                                }
                                                            ");

                                                            // Mostramos la notificación verde
                                                            Notification::make()
                                                                ->success()
                                                                ->title('¡Link copiado!')
                                                                ->body('El enlace ya está en tu portapapeles, listo para enviarse por WhatsApp o Correo.')
                                                                ->send();
                                                        }),
                                                    Action::make('export_pdf_tenant')->label('Exportar PDF')->color('warning'),
                                                ]),
                                            ]),

                                        // Sub-tab: Fiador
                                        Forms\Components\Tabs\Tab::make('Fiador')
                                            ->id('fiador')
                                            ->icon('heroicon-o-hand-raised')
                                            ->schema([
                                                Forms\Components\Section::make('Datos del Obligado solidario / Fiador')
                                                    ->schema([
                                                        Forms\Components\Select::make('tiene_fiador')
                                                            ->label('¿Tiene fiador u obligado solidario?')
                                                            ->options(['si' => 'Sí', 'no' => 'No'])
                                                            ->default('no')
                                                            ->live()
                                                            ->required(),

                                                        // === SE MUESTRA SOLO SI ES "SÍ" ===
                                                        Forms\Components\Group::make([
                                                            Forms\Components\Select::make('fiador_tipo_persona')
                                                                ->label('Tipo de Persona')
                                                                ->options(['fisica' => 'Persona física', 'moral' => 'Persona moral'])
                                                                ->live()
                                                                ->required(),

                                                            Forms\Components\Select::make('fiador_tipo')
                                                                ->label('Tipo')
                                                                ->options(['Obligado solidario' => 'Obligado solidario', 'Fiador' => 'Fiador'])
                                                                ->live()
                                                                ->required(),

                                                            // Campos para Persona Física
                                                            Forms\Components\TextInput::make('fiador_nombres')
                                                                ->label('Nombre(s)')
                                                                ->visible(fn (Forms\Get $get) => $get('fiador_tipo_persona') === 'fisica')
                                                                ->required(fn (Forms\Get $get) => $get('fiador_tipo_persona') === 'fisica'),
                                                            Forms\Components\TextInput::make('fiador_primer_apellido')
                                                                ->label('Primer Apellido')
                                                                ->visible(fn (Forms\Get $get) => $get('fiador_tipo_persona') === 'fisica')
                                                                ->required(fn (Forms\Get $get) => $get('fiador_tipo_persona') === 'fisica'),
                                                            Forms\Components\TextInput::make('fiador_segundo_apellido')
                                                                ->label('Segundo Apellido')
                                                                ->visible(fn (Forms\Get $get) => $get('fiador_tipo_persona') === 'fisica'),
                                                            Forms\Components\Select::make('fiador_sexo')
                                                                ->label('Sexo')
                                                                ->options(['Masculino' => 'Masculino', 'Femenino' => 'Femenino'])
                                                                ->visible(fn (Forms\Get $get) => $get('fiador_tipo_persona') === 'fisica'),

                                                            // Campos para Persona Moral
                                                            Forms\Components\TextInput::make('fiador_razon_social')
                                                                ->label('Nombre / Razón Social')
                                                                ->visible(fn (Forms\Get $get) => $get('fiador_tipo_persona') === 'moral')
                                                                ->required(fn (Forms\Get $get) => $get('fiador_tipo_persona') === 'moral')
                                                                ->columnSpan(2),
                                                            Forms\Components\TextInput::make('fiador_rfc')
                                                                ->label('RFC')
                                                                ->visible(fn (Forms\Get $get) => $get('fiador_tipo_persona') === 'moral'),

                                                            // Compartidos
                                                            Forms\Components\TextInput::make('fiador_email')
                                                                ->label('Correo')
                                                                ->email()
                                                                ->required(),
                                                        ])
                                                            ->columns(2)
                                                            ->visible(fn (Forms\Get $get) => $get('tiene_fiador') === 'si')
                                                            ->columnSpanFull(),
                                                    ]),

                                                Forms\Components\Actions::make([
                                                    Action::make('actualizar_fiador')
                                                        ->label('Guardar')
                                                        ->color('primary')
                                                        ->icon('heroicon-o-check')
                                                        ->action(function () {
                                                            // Guardamos los cambios básicos en la tabla de la Renta (Rent)
                                                            $this->save();

                                                            // Buscamos si el Fiador ya tiene una solicitud pública creada
                                                            $guarantorRequest = GuarantorRequest::where('rent_id', $this->record->id)->first();

                                                            // Si existe, la actualizamos con los datos frescos que acabamos de guardar
                                                            if ($guarantorRequest) {
                                                                // Obtenemos la versión más reciente de la renta para extraer los datos correctos
                                                                $rent = $this->record->fresh();

                                                                $guarantorRequest->update([
                                                                    'tipo_persona' => $rent->fiador_tipo_persona ?? 'fisica',
                                                                    'tipo_figura' => $rent->fiador_tipo ?? 'Fiador',
                                                                    'nombres' => $rent->fiador_nombres,
                                                                    'primer_apellido' => $rent->fiador_primer_apellido,
                                                                    'segundo_apellido' => $rent->fiador_segundo_apellido,
                                                                    'razon_social' => $rent->fiador_razon_social,
                                                                    'email' => $rent->fiador_email,
                                                                    'rfc' => $rent->fiador_rfc,
                                                                    'sexo' => $rent->fiador_sexo, // En caso de que se use
                                                                ]);
                                                            }

                                                            Notification::make()->success()->title('Configuración de fiador guardada y sincronizada')->send();

                                                            // Recargamos la vista para asegurar que no haya "fantasmas" visuales
                                                            $this->redirect(RentResource::getUrl('view', ['record' => $this->record]));
                                                        }),

                                                    // BOTONES QUE SOLO SALEN SI "tiene_fiador" ES SÍ
                                                    Action::make('edit_guarantor')
                                                        ->label(fn (Forms\Get $get) => 'Editar solicitud del '.strtolower($get('fiador_tipo') ?? 'fiador'))
                                                        ->color('primary')
                                                        ->visible(fn (Forms\Get $get) => $get('tiene_fiador') === 'si')
                                                        ->action(function () {
                                                            // Creamos el expediente en blanco atado a esta renta para ir a llenarlo
                                                            $guarantorRequest = GuarantorRequest::firstOrCreate(
                                                                ['rent_id' => $this->record->id],
                                                                [
                                                                    'tipo_persona' => $this->record->fiador_tipo_persona ?? 'fisica',
                                                                    'tipo_figura' => $this->record->fiador_tipo ?? 'Fiador',
                                                                    'nombres' => $this->record->fiador_nombres,
                                                                    'primer_apellido' => $this->record->fiador_primer_apellido,
                                                                    'segundo_apellido' => $this->record->fiador_segundo_apellido,
                                                                    'razon_social' => $this->record->fiador_razon_social,
                                                                    'email' => $this->record->fiador_email,
                                                                    'rfc' => $this->record->fiador_rfc,
                                                                    'estatus' => 'nueva',
                                                                ]
                                                            );
                                                            $this->redirect(GuarantorRequestResource::getUrl('edit', ['record' => $guarantorRequest]));
                                                        }),
                                                    Action::make('send_guarantor')
                                                        ->label(fn (Forms\Get $get) => 'Enviar solicitud al '.strtolower($get('fiador_tipo') ?? 'fiador'))
                                                        ->color('success')
                                                        ->visible(fn (Forms\Get $get) => $get('tiene_fiador') === 'si'),
                                                    Action::make('copy_link_guarantor')
                                                        ->label('Copiar link')
                                                        ->color('gray')
                                                        ->icon('heroicon-o-link')
                                                        ->visible(fn (Forms\Get $get) => $get('tiene_fiador') === 'si')
                                                        ->action(function (Action $action) {

                                                            // 1. Creamos o buscamos el expediente
                                                            $guarantorRequest = GuarantorRequest::firstOrCreate(
                                                                ['rent_id' => $this->record->id],
                                                                [
                                                                    'estatus' => 'nueva',
                                                                    'tipo_persona' => $this->record->fiador_tipo_persona ?? 'fisica',
                                                                    'tipo_figura' => $this->record->fiador_tipo ?? 'Fiador',
                                                                    'nombres' => $this->record->fiador_nombres,
                                                                    'primer_apellido' => $this->record->fiador_primer_apellido,
                                                                    'segundo_apellido' => $this->record->fiador_segundo_apellido,
                                                                    'email' => $this->record->fiador_email,
                                                                    'rfc' => $this->record->fiador_rfc,
                                                                ]
                                                            );

                                                            // 2. Armamos la URL
                                                            $urlPublica = route('solicitud.fiador.publica', $guarantorRequest->id);

                                                            // 3. Magia JS para copiar
                                                            $action->getLivewire()->js("
                                                                const texto = '{$urlPublica}';
                                                                if (navigator.clipboard && window.isSecureContext) {
                                                                    navigator.clipboard.writeText(texto);
                                                                } else {
                                                                    let textArea = document.createElement('textarea');
                                                                    textArea.value = texto;
                                                                    textArea.style.position = 'fixed';
                                                                    textArea.style.opacity = '0';
                                                                    document.body.appendChild(textArea);
                                                                    textArea.focus();
                                                                    textArea.select();
                                                                    try { document.execCommand('copy'); } catch (err) {}
                                                                    document.body.removeChild(textArea);
                                                                }
                                                            ");

                                                            Notification::make()->success()->title('¡Link copiado!')->body('El enlace del fiador está listo para enviarse.')->send();
                                                            $this->redirect(RentResource::getUrl('view', ['record' => $this->record]) . '?tab=solicitudes&solicitud=fiador');
                                                        }),
                                                ]),
                                            ]),

                                        // Sub-tab: Propietario
                                        Forms\Components\Tabs\Tab::make('Propietario')
                                            ->id('propietario')
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
                                                                        $nombre = trim(($owner->nombres ?? '').' '.($owner->primer_apellido ?? '').' '.($owner->segundo_apellido ?? ''));
                                                                    } else {
                                                                        $nombre = $owner->razon_social ?? '';
                                                                    }
                                                                    $label = ($nombre ?: 'Sin nombre').' - '.($owner->email ?? '');
                                                                    $options[$owner->id] = $label;
                                                                }

                                                                return $options;
                                                            })
                                                            ->getOptionLabelUsing(function ($value) {
                                                                if (! $value) {
                                                                    return null;
                                                                }
                                                                $owner = Owner::find($value);
                                                                if (! $owner) {
                                                                    return $value;
                                                                }

                                                                if ($owner->tipo_persona === 'fisica') {
                                                                    $nombre = trim(($owner->nombres ?? '').' '.($owner->primer_apellido ?? '').' '.($owner->segundo_apellido ?? ''));
                                                                } else {
                                                                    $nombre = $owner->razon_social ?? '';
                                                                }

                                                                return ($nombre ?: 'Sin nombre').' - '.($owner->email ?? '');
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

                                                                        Notification::make()
                                                                            ->success()
                                                                            ->title('Propietario vinculado')
                                                                            ->body('Los datos del propietario se han actualizado.')
                                                                            ->send();
                                                                            $this->redirect(RentResource::getUrl('view', ['record' => $this->record]) . '?tab=-solicitudes-tab&solicitud=-propietario-tab');

                                                                    }
                                                                }
                                                            }),
                                                        // === FIN SELECT DE OWNERS ===

                                                        Forms\Components\Placeholder::make('current_owner_info')
                                                            ->label('Información actual del propietario')
                                                            ->content(function () {
                                                                $owner = $this->record->owner;
                                                                if (! $owner) {
                                                                    return 'No hay propietario asignado';
                                                                }
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
                                                            ->required(fn (Forms\Get $get): bool => filled($get('owner_id')))
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
                                                    Action::make('actualizar_propietario')
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

                                                                // Sincronizamos la solicitud (OwnerRequest) si ya existe
                                                                $ownerRequest = OwnerRequest::where('owner_id', $this->record->owner_id)
                                                                    ->where('rent_id', $this->record->id)
                                                                    ->first();

                                                                if ($ownerRequest) {
                                                                    $ownerRequest->update($updateData);
                                                                }

                                                                // Persistir owner_id si está presente
                                                                if (isset($this->data['owner_id'])) {
                                                                    $this->record->update(['owner_id' => $this->data['owner_id']]);
                                                                }

                                                                Notification::make()->success()->title('Propietario actualizado')->send();
                                                                $this->redirect(RentResource::getUrl('view', ['record' => $this->record]));
                                                            }
                                                        }),
                                                    Action::make('edit_owner')
                                                        ->label('Ver solicitud del propietario')
                                                        ->color('primary')
                                                        ->action(function () {
                                                            $ownerRequest = OwnerRequest::where('owner_id', $this->record->owner_id)
                                                                ->where('rent_id', $this->record->id)->first();
                                                            if (! $ownerRequest) {
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
                                                    Action::make('send_owner')->label('Enviar solicitud al propietario')->color('success'),
                                                    Action::make('copy_link_owner')
                                                        ->label('Copiar link')
                                                        ->color('gray')
                                                        ->icon('heroicon-o-link')
                                                        ->visible(fn () => $this->record->owner)
                                                        ->action(function (Action $action) {

                                                            // Nos aseguramos de que el expediente exista en la BD
                                                            $ownerRequest = OwnerRequest::firstOrCreate(
                                                                [
                                                                    'owner_id' => $this->record->owner_id,
                                                                    'rent_id' => $this->record->id,
                                                                ],
                                                                [
                                                                    'estatus' => 'nueva',
                                                                    'nombres' => $this->record->owner->nombres,
                                                                    'primer_apellido' => $this->record->owner->primer_apellido,
                                                                    'segundo_apellido' => $this->record->owner->segundo_apellido,
                                                                    'email' => $this->record->owner->email,
                                                                    'rfc' => $this->record->owner->rfc,
                                                                ]
                                                            );

                                                            // Armamos la URL pública real del propietario
                                                            $urlPublica = route('solicitud.propietario.publica', $ownerRequest->id);

                                                            $action->getLivewire()->js("
                                                                const texto = '{$urlPublica}';
                                                                
                                                                // Intento 1: API Moderna
                                                                if (navigator.clipboard && window.isSecureContext) {
                                                                    navigator.clipboard.writeText(texto);
                                                                } else {
                                                                    // Intento 2: Fallback tradicional
                                                                    let textArea = document.createElement('textarea');
                                                                    textArea.value = texto;
                                                                    textArea.style.position = 'fixed';
                                                                    textArea.style.opacity = '0';
                                                                    document.body.appendChild(textArea);
                                                                    textArea.focus();
                                                                    textArea.select();
                                                                    try {
                                                                        document.execCommand('copy');
                                                                    } catch (err) {
                                                                        console.error('No se pudo copiar', err);
                                                                    }
                                                                    document.body.removeChild(textArea);
                                                                }
                                                            ");

                                                            // Mostramos notificación de éxito
                                                            Notification::make()
                                                                ->success()
                                                                ->title('¡Link copiado!')
                                                                ->body('El enlace de la solicitud del propietario ya está en tu portapapeles.')
                                                                ->send();
                                                        }),
                                                    Action::make('export_pdf_owner')->label('Exportar PDF')->color('warning'),
                                                ]),
                                            ]),

                                        // Sub-tab: Propiedad
                                        Forms\Components\Tabs\Tab::make('Propiedad')
                                            ->id('propiedad')
                                            ->icon('heroicon-o-building-office')
                                            ->schema([
                                                Forms\Components\Section::make('Datos de la propiedad')
                                                    ->schema([
                                                        // === SELECT DE PROPERTIES DISPONIBLES ===
                                                        Forms\Components\Select::make('property_id')
                                                            ->label('Seleccionar Propiedad Disponible')
                                                            ->options(function () {
                                                                $ownerUserId = $this->record->owner?->user_id;
                                                                $selectedPropertyId = $this->record->property_id;

                                                                $properties = Property::query()
                                                                    ->when($ownerUserId, fn ($q) => $q->where('user_id', $ownerUserId))
                                                                    ->where(function ($q) use ($selectedPropertyId) {
                                                                        $q->where('estatus', 'disponible');
                                                                        if ($selectedPropertyId) {
                                                                            $q->orWhere('id', $selectedPropertyId);
                                                                        }
                                                                    })
                                                                    ->orderBy('created_at', 'desc')
                                                                    ->get();

                                                                $options = [];
                                                                foreach ($properties as $property) {
                                                                    $direccion = trim(($property->calle ?? '').' '.($property->numero_exterior ?? ''));
                                                                    $label = ($property->folio ?? 'N/A').' - '.($direccion ?: 'Sin dirección');
                                                                    $options[$property->id] = $label;
                                                                }

                                                                return $options;
                                                            })
                                                            ->getOptionLabelUsing(function ($value) {
                                                                if (! $value) {
                                                                    return null;
                                                                }
                                                                $property = Property::find($value);
                                                                if (! $property) {
                                                                    return $value;
                                                                }

                                                                $direccion = trim(($property->calle ?? '').' '.($property->numero_exterior ?? ''));

                                                                return ($property->folio ?? 'N/A').' - '.($direccion ?: 'Sin dirección');
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
                                                                        $this->record->update([
                                                                            'property_id' => $state,
                                                                            'tipo_propiedad' => $property->tipo_inmueble ?? null,
                                                                            'calle' => $property->calle ?? null,
                                                                            'numero_exterior' => $property->numero_exterior ?? null,
                                                                            'numero_interior' => $property->numero_interior ?? null,
                                                                            'codigo_postal' => $property->codigo_postal ?? null,
                                                                            'colonia' => $property->colonia ?? null,
                                                                            'municipio' => $property->delegacion_municipio ?? null,
                                                                            'estado' => $property->estado ?? null,
                                                                            'referencias_ubicacion' => $property->referencias_ubicacion ?? null,
                                                                        ]);

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

                                                                        Notification::make()
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
                                                            ->nullable(),
                                                        Forms\Components\TextInput::make('codigo_postal')
                                                            ->label('CP')
                                                            ->numeric()
                                                            ->maxLength(5),
                                                    ])
                                                    ->columns(2),

                                                Forms\Components\Actions::make([
                                                    Action::make('guardar_propiedad')
                                                        ->label('Guardar')
                                                        ->color('primary')
                                                        ->icon('heroicon-o-check')
                                                        ->action(function () {
                                                            // Persistir property_id si está presente
                                                            if (isset($this->data['property_id'])) {
                                                                $this->record->update(['property_id' => $this->data['property_id']]);
                                                            }

                                                            $this->save();
                                                            Notification::make()->success()->title('Datos de propiedad guardados')->send();
                                                        }),
                                                ]),
                                            ]),
                                    ]),
                            ]),

                        // ========== TAB: DOCUMENTOS ==========
                        Forms\Components\Tabs\Tab::make('Documentos')
                            ->icon('heroicon-o-document-text')
                            ->visible(fn (): bool => $this->record->estatus !== 'nueva')
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
                                                        Action::make('subir_doc_inquilino')
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
                                                                Notification::make()->success()->title('Documento cargado')->send();
                                                            }),
                                                    ])
                                                    ->schema([
                                                        Forms\Components\Placeholder::make('tenant_docs_grid')
                                                            ->hiddenLabel()
                                                            ->content(function () {
                                                                $docs = $this->record->tenantDocuments;
                                                                if ($docs->isEmpty()) {
                                                                    return new HtmlString('
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

                                                                return new HtmlString($html);
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
                                                        Action::make('subir_doc_fiador')
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
                                                                Notification::make()->success()->title('Documento cargado')->send();
                                                            }),
                                                    ])
                                                    ->schema([
                                                        Forms\Components\Placeholder::make('guarantor_docs_grid')
                                                            ->hiddenLabel()
                                                            ->content(function () {
                                                                $docs = $this->record->guarantorDocuments;
                                                                if ($docs->isEmpty()) {
                                                                    return new HtmlString('
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

                                                                return new HtmlString($html);
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
                                                        Action::make('subir_doc_propietario')
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
                                                                Notification::make()->success()->title('Documento cargado')->send();
                                                            }),
                                                    ])
                                                    ->schema([
                                                        Forms\Components\Placeholder::make('owner_docs_grid')
                                                            ->hiddenLabel()
                                                            ->content(function () {
                                                                $docs = $this->record->ownerDocuments;
                                                                if ($docs->isEmpty()) {
                                                                    return new HtmlString('
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

                                                                return new HtmlString($html);
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
                                                        Action::make('subir_doc_propiedad')
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
                                                                Notification::make()->success()->title('Documento cargado')->send();
                                                            }),
                                                    ])
                                                    ->schema([
                                                        Forms\Components\Placeholder::make('property_docs_grid')
                                                            ->hiddenLabel()
                                                            ->content(function () {
                                                                $docs = $this->record->propertyDocuments;
                                                                if ($docs->isEmpty()) {
                                                                    return new HtmlString('
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

                                                                return new HtmlString($html);
                                                            }),
                                                    ]),
                                            ]),
                                    ]),
                            ]),

                        // ========== TAB: PROCESO DE RENTA ==========
                        Forms\Components\Tabs\Tab::make('Proceso de Renta')
                            ->icon('heroicon-o-shield-check')
                            ->visible(fn (): bool => $this->record->estatus !== 'nueva')
                            ->schema([
                                Forms\Components\Section::make('Resumen de la Operación')
                                    ->description('Verifique los datos antes de enviar el expediente al abogado de Póliza de Rentas.')
                                    ->schema([

                                        // Resumen del Inquilino
                                        Forms\Components\Placeholder::make('resumen_inquilino')
                                            ->label('Datos del Inquilino')
                                            ->content(fn ($record) => new HtmlString(
                                                $record->tenant
                                                ? "<b>Nombre:</b> {$record->tenant->nombre_completo}<br><b>Email:</b> {$record->tenant->email}<br><b>Teléfono:</b> ".($record->tenant->telefono_celular ?? $record->tenant->telefono)
                                                : '<span class="text-red-500">Sin asignar</span>'
                                            )),

                                        // Resumen del Propietario
                                        Forms\Components\Placeholder::make('resumen_propietario')
                                            ->label('Datos del Propietario')
                                            ->content(fn ($record) => new HtmlString(
                                                $record->owner
                                                ? "<b>Nombre:</b> {$record->owner->nombre_completo}<br><b>Email:</b> {$record->owner->email}<br><b>Teléfono:</b> {$record->owner->telefono}"
                                                : '<span class="text-red-500">Sin asignar</span>'
                                            )),

                                        // Propiedad y Renta
                                        Forms\Components\Placeholder::make('resumen_inmueble')
                                            ->label('Inmueble y Propiedad')
                                            ->content(fn ($record) => new HtmlString(
                                                '<b>Tipo:</b> '.ucfirst($record->tipo_inmueble ?? 'N/A').'<br>'.
                                                '<b>Dirección:</b> '.trim(($record->calle ?? '').' '.($record->numero_exterior ?? ''))
                                            )),

                                        Forms\Components\Placeholder::make('resumen_renta')
                                            ->label('Monto y Solicitud')
                                            ->content(fn ($record) => new HtmlString(
                                                '<b>Renta Mensual:</b> $'.number_format($record->renta ?? 0, 2).'<br>'.
                                                '<b>Solicitud Inquilino:</b> '.($record->application ? $record->application->folio : 'No vinculada')
                                            )),

                                        // Fechas y Plazos (Proceso de Renta)
                                        Forms\Components\Select::make('plazo_arrendamiento')
                                            ->label('Plazo del arrendamiento')
                                            ->options([
                                                '3' => '3 meses',
                                                '6' => '6 meses',
                                                '12' => '12 meses',
                                                '18' => '18 meses',
                                                '24' => '24 meses',
                                            ])
                                            ->live()
                                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set): void {
                                                $this->syncResumenEndDate($get, $set);
                                            })
                                            ->helperText('La fecha de fin se calcula sola a partir del plazo y la fecha de inicio. Obligatorio al enviar a Póliza de Rentas.'),

                                        Forms\Components\DatePicker::make('start_date')
                                            ->label('Fecha de inicio')
                                            ->displayFormat('d/m/Y')
                                            ->native(false)
                                            ->live()
                                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set): void {
                                                $this->syncResumenEndDate($get, $set);
                                            }),

                                        Forms\Components\DatePicker::make('end_date')
                                            ->label('Fecha de fin')
                                            ->displayFormat('d/m/Y')
                                            ->native(false)
                                            ->disabled()
                                            ->dehydrated()
                                            ->helperText('Calculada automáticamente: un día antes de cumplirse el plazo (inicio + meses − 1 día).'),

                                        Forms\Components\DatePicker::make('fecha_firma')
                                            ->label('Fecha prevista de firma')
                                            ->displayFormat('d/m/Y')
                                            ->native(false),

                                        Forms\Components\Toggle::make('con_poliza')
                                            ->label('¿Con póliza?')
                                            ->helperText('Si está activado, se habilita el envío a Póliza de Rentas. Si no, podrás activar la renta directamente.')
                                            ->onColor('success')
                                            ->offColor('gray')
                                            ->live(),

                                        // Tipo de Póliza (condicional, solo si "con_poliza" está activo)
                                        Forms\Components\Select::make('tipo_poliza')
                                            ->label('Tipo de Póliza')
                                            ->options(function ($record) {
                                                // Opciones base para todos
                                                $opciones = [
                                                    'PÓLIZA AMPLIA' => 'Póliza Amplia',
                                                    'PÓLIZA INTEGRAL' => 'Póliza Integral',
                                                ];

                                                // Si el inquilino existe y es persona física, agregamos la opción con seguro
                                                if ($record && $record->tenant?->tipo_persona === 'fisica') {
                                                    $opciones['PÓLIZA CON SEGURO'] = 'Póliza con Seguro';
                                                }

                                                return $opciones;
                                            })
                                            ->visible(fn (Forms\Get $get) => (bool) $get('con_poliza'))
                                            ->required(fn (Forms\Get $get) => (bool) $get('con_poliza')),
                                    ])->columns(2),

                                // Botón de Envío
                                Forms\Components\Actions::make([
                                    Action::make('enviar_pdr')
                                        ->label('Enviar a Póliza de Rentas')
                                        ->icon('heroicon-m-paper-airplane')
                                        ->color('success')
                                        ->requiresConfirmation()
                                        ->modalHeading('¿Enviar Expediente a Póliza de Rentas?')
                                        ->modalDescription('El estatus de la renta cambiará automáticamente a "Análisis" y se enviará la información estructurada a PDR.')
                                        ->action(function (Forms\Get $get, Forms\Set $set, $record) {
                                                
                                                // === PÓLIZA CON SEGURO SOLO PARA FÍSICAS ===
                                                if ($get('tipo_poliza') === 'PÓLIZA CON SEGURO' && $record->tenant?->tipo_persona !== 'fisica') {
                                                    Notification::make()
                                                        ->danger()
                                                        ->title('Operación no permitida')
                                                        ->body('La Póliza con Seguro es exclusiva para inquilinos registrados como Persona Física.')
                                                        ->send();
                                                    return;
                                                }

                                                // === VALIDACIÓN DE CAMPOS EXTRA ===
                                                if ($get('tipo_poliza') === 'PÓLIZA CON SEGURO' && $record->tenant?->tipo_persona === 'fisica') {
                                                    
                                                    // Buscamos la solicitud atada a esta renta
                                                    $tenantRequest = \App\Models\TenantRequest::where('tenant_id', $record->tenant_id)
                                                                                              ->where('rent_id', $record->id)
                                                                                              ->first();

                                                    // Si no hay solicitud, o si los campos obligatorios de la póliza con seguro están vacíos
                                                    if (!$tenantRequest || is_null($tenantRequest->metros_cuadrados) || is_null($tenantRequest->nacionalidad)) {
                                                        
                                                        // Guardamos los datos antes de redirigir
                                                        $record->update([
                                                            'tipo_poliza'         => $get('tipo_poliza'),
                                                            'plazo_arrendamiento' => $get('plazo_arrendamiento'),
                                                            'start_date'          => $get('start_date'),
                                                            'end_date'            => $get('end_date'),
                                                            'fecha_firma'         => $get('fecha_firma'),
                                                        ]);

                                                        Notification::make()
                                                            ->warning()
                                                            ->title('Faltan datos obligatorios')
                                                            ->body('La Póliza con Seguro requiere información adicional. Redirigiendo a la solicitud...')
                                                            ->send();
                                                            
                                                        // Si no existía, la creamos
                                                        if (!$tenantRequest) {
                                                            $tenantRequest = \App\Models\TenantRequest::create([
                                                                'tenant_id' => $record->tenant_id,
                                                                'rent_id'   => $record->id,
                                                                'estatus'   => 'nueva'
                                                            ]);
                                                        }
                                                        
                                                        // Cancelamos el envío a PDR y redirigimos al form del inquilino
                                                        return redirect(\App\Filament\Resources\TenantRequestResource::getUrl('edit', ['record' => $tenantRequest]));
                                                    }
                                                }

                                            $payloadValidacion = [
                                                'plazo_arrendamiento' => $get('plazo_arrendamiento'),
                                                'start_date' => $get('start_date'),
                                                'end_date' => $get('end_date'),
                                                'fecha_firma' => $get('fecha_firma'),
                                                'tipo_poliza' => $get('tipo_poliza'), // Capturamos el tipo de póliza
                                            ];

                                            try {
                                                Validator::make($payloadValidacion, [
                                                    'plazo_arrendamiento' => ['required', 'in:3,6,12,18,24'],
                                                    'start_date' => ['required', 'date'],
                                                    'end_date' => ['required', 'date'],
                                                    'fecha_firma' => ['required', 'date'],
                                                ], [], [
                                                    'plazo_arrendamiento' => 'plazo del arrendamiento',
                                                    'start_date' => 'fecha de inicio',
                                                    'end_date' => 'fecha de fin',
                                                    'fecha_firma' => 'fecha prevista de firma',
                                                ])->validate();
                                            } catch (ValidationException $e) {
                                                Notification::make()
                                                    ->danger()
                                                    ->title('Completa el resumen de la operación')
                                                    ->body(collect($e->errors())->flatten()->implode(' '))
                                                    ->send();
                                                return;
                                            }

                                            // === DELEGAMOS EL TRABAJO AL SERVICIO ===
                                            $pdrService = app(\App\Services\PdrApi\PdrApiService::class);
                                            $resultado = $pdrService->enviarExpedienteYActualizar($record, $payloadValidacion);

                                            if ($resultado['success']) {
                                                $set('estatus', 'analisis'); 
                                                Notification::make()
                                                    ->success()
                                                    ->title('Expediente enviado exitosamente')
                                                    ->body('El estatus ha cambiado a Análisis y la Póliza ha sido solicitada.')
                                                    ->send();
                                            } else {
                                                 Notification::make()
                                                    ->danger()
                                                    ->title('Error al conectar con PDR')
                                                    ->body($resultado['error'] ?? 'Ocurrió un error inesperado al enviar los datos.')
                                                    ->send();
                                            }
                                        })
                                        ->visible(fn (Forms\Get $get, $record) => (bool) $get('con_poliza') && in_array($record->estatus, ['nueva', 'documentacion'])),
                                ])->fullWidth(),
                            ]),

                        // ========== TAB: ADMINISTRACIÓN ==========
                        Forms\Components\Tabs\Tab::make('Administración')
                            ->icon('heroicon-o-briefcase')
                            ->visible(fn (): bool => $this->record->estatus === 'activa')
                            ->schema([
                                Forms\Components\Section::make('Gestión de la Propiedad')
                                    ->description('Si el agente administra, la renta se gestiona desde “Mis Administraciones”. Si no, se gestiona desde aquí.')
                                    ->schema([
                                        // EL CHECK PRINCIPAL: ¿Lo administra el agente o el propietario?
                                        Forms\Components\Toggle::make('is_administrada_por_agente')
                                            ->label('¿El agente administrará esta propiedad?')
                                            ->helperText('Encendido: se gestiona desde “Mis Administraciones”. Apagado: se gestiona en esta pestaña.')
                                            ->onColor('success')
                                            ->offColor('gray')
                                            ->live(),

                                        Forms\Components\Placeholder::make('admin_hint')
                                            ->hiddenLabel()
                                            ->content(fn (Forms\Get $get) => (bool) $get('is_administrada_por_agente')
                                                ? 'Esta renta se administra desde “Mis Administraciones”.'
                                                : 'Esta renta se administra desde esta pestaña.'),

                                        Forms\Components\Grid::make(2)->schema([
                                            Forms\Components\TextInput::make('dia_cobro_renta')
                                                ->label('Día de cobro mensual')
                                                ->numeric()
                                                ->minValue(1)
                                                ->maxValue(31)
                                                ->placeholder('Ej. 5 (para el día 5 del mes)')
                                                ->suffix('del mes'),

                                            Forms\Components\Textarea::make('notas_administracion')
                                                ->label('Notas internas de administración')
                                                ->rows(2),
                                        ]),

                                        Forms\Components\Fieldset::make('Configuración de Alertas (Recordatorios Automáticos)')
                                            ->schema([
                                                Forms\Components\Toggle::make('enviar_recordatorio_inquilino')
                                                    ->label('Enviar recordatorio de pago al Inquilino')
                                                    ->default(true),

                                                Forms\Components\Toggle::make('enviar_recordatorio_propietario')
                                                    ->label('Enviar aviso de cobro al Propietario')
                                                    ->default(true)
                                                    // Si el agente administra, sugerimos apagar las alertas al propietario
                                                    ->disabled(fn (Forms\Get $get) => $get('is_administrada_por_agente'))
                                                    ->dehydrated()
                                                    ->helperText(fn (Forms\Get $get) => $get('is_administrada_por_agente') ? 'Desactivado porque el agente administra la propiedad.' : ''),
                                            ])->columns(2),

                                        // Botón de Envío
                                        Forms\Components\Actions::make([
                                            Action::make('guardar_administracion')
                                                ->label('Guardar Configuración')
                                                ->color('primary')
                                                ->icon('heroicon-o-check')
                                                ->action(function () {
                                                    $this->save();
                                                    Notification::make()->success()->title('Configuración guardada')->send();
                                                }),
                                        ]),
                                    ]),

                                Forms\Components\Group::make()
                                    ->visible(fn (Forms\Get $get): bool => ! (bool) $get('is_administrada_por_agente'))
                                    ->schema([
                                        AdministrationTabs::make($this->record),
                                    ]),
                            ]),
                    ]),

                // SECCIÓN GLOBAL DE COMENTARIOS Y BITÁCORA
                Forms\Components\Section::make('Bitácora y Comentarios')
                    ->description('Historial de la operación y notas de seguimiento.')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'mt-4 bg-gray-50 dark:bg-white/5'])
                    ->schema([
                        Forms\Components\Tabs::make('TabsComentarios')
                            ->tabs([

                                // === PESTAÑA 1: COMENTARIOS (Por defecto) ===
                                Forms\Components\Tabs\Tab::make('Comentarios')
                                    ->icon('heroicon-m-chat-bubble-bottom-center-text')
                                    ->badge(fn () => $this->record->comments()->where('comment', 'not like', 'El sistema registró%')->count())
                                    ->schema([
                                        Forms\Components\Group::make()->schema([
                                            Forms\Components\Textarea::make('new_comment_content')
                                                ->hiddenLabel()
                                                ->placeholder('Escribe una nueva nota o comentario...')
                                                ->rows(2)
                                                ->extraInputAttributes(['class' => 'border-gray-300 focus:border-[#26cad3] focus:ring-[#26cad3]']),

                                            Forms\Components\Actions::make([
                                                Action::make('guardar_comentario')
                                                    ->label('Publicar Comentario')
                                                    ->color('primary')
                                                    ->icon('heroicon-m-paper-airplane')
                                                    ->action(function (Forms\Get $get, Forms\Set $set) {
                                                        $content = $get('new_comment_content');
                                                        if (! $content) {
                                                            return;
                                                        }

                                                        RentComment::create([
                                                            'rent_id' => $this->record->id,
                                                            'user_id' => auth()->id(),
                                                            'comment' => $content,
                                                            'status' => 'activa',
                                                        ]);

                                                        $set('new_comment_content', '');
                                                        Notification::make()->success()->title('Comentario registrado')->send();
                                                    }),
                                            ])->alignRight(),
                                        ]),

                                        Forms\Components\Placeholder::make('comments_list_manual')
                                            ->hiddenLabel()
                                            ->content(function () {
                                                // Filtramos para excluir los mensajes del sistema
                                                $comments = $this->record->comments()
                                                    ->where('comment', 'not like', 'El sistema registró%')
                                                    ->with('user')
                                                    ->orderBy('created_at', 'desc')
                                                    ->get();

                                                if ($comments->isEmpty()) {
                                                    return new HtmlString('
                                                        <div class="flex flex-col items-center justify-center p-8 text-center bg-white border border-gray-200 border-dashed rounded-xl dark:bg-gray-800 dark:border-gray-700 mt-4">
                                                            <p class="text-sm text-gray-500">Sin comentarios aún</p>
                                                        </div>
                                                    ');
                                                }

                                                $html = '<div class="space-y-3 max-h-[400px] overflow-y-auto pr-2 mt-4">';
                                                foreach ($comments as $comment) {
                                                    $userName = $comment->user->name ?? 'Usuario Desconocido';
                                                    $date = $comment->created_at->format('d M Y, h:i A');

                                                    $initials = collect(explode(' ', $userName))->map(fn ($w) => strtoupper(substr($w, 0, 1)))->take(2)->implode('');

                                                    $html .= "
                                                        <div class='flex items-start gap-3'>
                                                            <div class='flex-shrink-0'>
                                                                <div class='flex items-center justify-center w-10 h-10 rounded-full bg-[#161848] text-white text-xs font-bold shadow-sm'>
                                                                    {$initials}
                                                                </div>
                                                            </div>
                                                            <div class='flex-1 min-w-0'>
                                                                <div class='bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg rounded-tl-none shadow-sm p-4'>
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

                                                return new HtmlString($html);
                                            }),
                                    ]),

                                // === PESTAÑA 2: BITÁCORA DEL SISTEMA ===
                                Forms\Components\Tabs\Tab::make('Bitácora')
                                    ->icon('heroicon-m-clipboard-document-list')
                                    ->badge(fn () => $this->record->comments()->where('comment', 'like', 'El sistema registró%')->count())
                                    ->badgeColor('info')
                                    ->schema([
                                        Forms\Components\Placeholder::make('comments_list_system')
                                            ->hiddenLabel()
                                            ->content(function () {
                                                // Filtramos para incluir SOLO los mensajes del sistema
                                                $comments = $this->record->comments()
                                                    ->where('comment', 'like', 'El sistema registró%')
                                                    ->with('user')
                                                    ->orderBy('created_at', 'desc')
                                                    ->get();

                                                if ($comments->isEmpty()) {
                                                    return new HtmlString('
                                                        <div class="flex flex-col items-center justify-center p-8 text-center bg-white border border-gray-200 border-dashed rounded-xl dark:bg-gray-800 dark:border-gray-700 mt-4">
                                                            <p class="text-sm text-gray-500">Sin registros en la bitácora aún</p>
                                                        </div>
                                                    ');
                                                }

                                                $html = '<div class="space-y-3 max-h-[400px] overflow-y-auto pr-2 mt-4">';
                                                foreach ($comments as $comment) {
                                                    $userName = $comment->user->name ?? 'Sistema';
                                                    $date = $comment->created_at->format('d M Y, h:i A');

                                                    $initials = collect(explode(' ', $userName))->map(fn ($w) => strtoupper(substr($w, 0, 1)))->take(2)->implode('');

                                                    $html .= "
                                                        <div class='flex items-start gap-3 opacity-90 hover:opacity-100 transition-opacity'>
                                                            <div class='flex-shrink-0'>
                                                                <div class='flex items-center justify-center w-8 h-8 rounded-full bg-[#26cad3] text-white text-xs font-bold shadow-sm mt-1'>
                                                                    {$initials}
                                                                </div>
                                                            </div>
                                                            <div class='flex-1 min-w-0'>
                                                                <div class='bg-blue-50 dark:bg-blue-900/20 border border-[#26cad3]/30 rounded-lg rounded-tl-none shadow-sm p-4'>
                                                                    <div class='flex items-center justify-between mb-2'>
                                                                        <span class='text-sm font-bold text-[#161848] dark:text-white'>{$userName} <span class='font-normal text-gray-500'>actualizó el registro:</span></span>
                                                                        <span class='text-xs text-gray-400'>{$date}</span>
                                                                    </div>
                                                                    <p class='text-sm text-gray-700 dark:text-gray-200 whitespace-pre-wrap leading-relaxed'>".str_replace(['El sistema registró las siguientes actualizaciones en el expediente:', 'El sistema registró los siguientes cambios:'], '', $comment->comment).'</p>
                                                                </div>
                                                            </div>
                                                        </div>';
                                                }
                                                $html .= '</div>';

                                                return new HtmlString($html);
                                            }),
                                    ]),
                            ]),
                    ]),
            ]);
    }
}
