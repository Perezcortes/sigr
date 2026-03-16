<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SaleResource\Pages;
use App\Models\Sale;
use App\Models\Office;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Actions;

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationLabel = 'Ventas';
    protected static ?string $modelLabel = 'Proceso de Venta';
    protected static ?string $pluralModelLabel = 'Ventas';
    protected static ?string $navigationGroup = 'Ventas';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // VISTA DE CREACIÓN
                Forms\Components\Section::make('Información de la Venta')
                    ->visible(fn ($livewire) => $livewire instanceof Pages\CreateSale)
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            // 1. Selector de Comprador con opción de Crear Nuevo
                            Forms\Components\Select::make('buyer_id')
                                ->label('SELECCIONAR COMPRADOR*')
                                ->relationship('buyer', 'nombres', function (Builder $query) {
                                    /** @var \App\Models\User $user */
                                    $user = auth()->user();
                                    if ($user->hasRole('Administrador')) { return $query; }
                                    return $query->where('user_id', $user->id)->orWhereNull('user_id');
                                })
                                ->getOptionLabelFromRecordUsing(fn ($record) => 
                                    "{$record->nombre_completo} | {$record->email} - " . ($record->asesor ? "Asesor: {$record->asesor->name}" : "SIN ASESOR")
                                )
                                ->searchable(['nombres', 'ap_paterno', 'email'])
                                ->preload()
                                ->required()
                                ->createOptionForm([
                                    Forms\Components\Select::make('tipo_persona')->label('Tipo de Persona*')->options(['fisica' => 'Persona física', 'moral' => 'Persona moral'])->default('fisica')->required()->live(),
                                    Forms\Components\TextInput::make('nombres')->label('Nombre(s)*')->required(),
                                    Forms\Components\TextInput::make('ap_paterno')->label('Primer Apellido*')->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')->required(),
                                    Forms\Components\TextInput::make('email')->label('Correo Electrónico*')->email()->required(),
                                    Forms\Components\TextInput::make('celular')->label('Teléfono Celular*')->tel()->required(),
                                ]),

                            // 2. Selector de Vendedor con opción de Crear Nuevo
                            Forms\Components\Select::make('seller_id')
                                ->label('SELECCIONAR PROPIETARIO / VENDEDOR*')
                                ->relationship('seller', 'nombres', function (Builder $query) {
                                    /** @var \App\Models\User $user */
                                    $user = auth()->user();
                                    if ($user->hasRole('Administrador')) { return $query; }
                                    return $query->where('user_id', $user->id)->orWhereNull('user_id');
                                })
                                ->getOptionLabelFromRecordUsing(fn ($record) => 
                                    "{$record->nombre_completo} | {$record->email} - " . ($record->asesor ? "Asesor: {$record->asesor->name}" : "SIN ASESOR")
                                )
                                ->searchable(['nombres', 'ap_paterno', 'email'])
                                ->preload()
                                ->required()
                                ->createOptionForm([
                                    Forms\Components\Select::make('tipo_persona')->label('Tipo de Persona*')->options(['fisica' => 'Persona física', 'moral' => 'Persona moral'])->default('fisica')->required()->live(),
                                    Forms\Components\TextInput::make('nombres')->label('Nombre(s)*')->required(),
                                    Forms\Components\TextInput::make('ap_paterno')->label('Primer Apellido*')->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')->required(),
                                    Forms\Components\TextInput::make('email')->label('Correo Electrónico*')->email()->required(),
                                    Forms\Components\TextInput::make('celular')->label('Teléfono Celular*')->tel()->required(),
                                ]),
                        ]),
                    ]),

                // VISTA DE EDICIÓN: El expediente completo (tabs)
                Forms\Components\Tabs::make('Proceso de Venta')
                    ->visible(fn ($livewire) => $livewire instanceof Pages\EditSale)
                    ->columnSpanFull()
                    ->tabs([
                        
                        // PESTAÑA 1: COMPRADOR
                        Forms\Components\Tabs\Tab::make('1. Comprador')
                            ->icon('heroicon-o-user')
                            ->schema([
                                Forms\Components\Section::make('Datos del Comprador Principal')
                                    ->description('Información cargada automáticamente del perfil del comprador.')
                                    ->schema([
                                        Forms\Components\Grid::make(3)->schema([
                                            Forms\Components\TextInput::make('comprador_nombres')->label('Nombre(s)')->default(fn($record) => $record?->buyer?->nombres)->required(),
                                            Forms\Components\TextInput::make('comprador_ap_paterno')->label('Apellido Paterno')->default(fn($record) => $record?->buyer?->ap_paterno)->required(),
                                            Forms\Components\TextInput::make('comprador_ap_materno')->label('Apellido Materno')->default(fn($record) => $record?->buyer?->ap_materno),
                                        ]),
                                        Forms\Components\Grid::make(2)->schema([
                                            Forms\Components\TextInput::make('comprador_telefono')->tel()->label('Teléfono Fijo')->default(fn($record) => $record?->buyer?->telefono),
                                            Forms\Components\TextInput::make('comprador_celular')->tel()->label('Celular')->default(fn($record) => $record?->buyer?->celular),
                                            Forms\Components\TextInput::make('comprador_email')->email()->label('Email')->default(fn($record) => $record?->buyer?->email),
                                            Forms\Components\DatePicker::make('comprador_fecha_nacimiento')->label('Fecha Nacimiento')->default(fn($record) => $record?->buyer?->fecha_nacimiento),
                                            Forms\Components\TextInput::make('comprador_rfc')->label('RFC')->default(fn($record) => $record?->buyer?->rfc),
                                            Forms\Components\TextInput::make('comprador_curp')->label('CURP')->default(fn($record) => $record?->buyer?->curp),
                                        ]),
                                        Forms\Components\Fieldset::make('Dirección del Comprador Principal')->schema([
                                            Forms\Components\TextInput::make('comprador_calle')->label('Calle y Número')->default(fn($record) => $record?->buyer?->calle),
                                            Forms\Components\TextInput::make('comprador_colonia')->label('Colonia')->default(fn($record) => $record?->buyer?->colonia),
                                            Forms\Components\TextInput::make('comprador_ciudad')->label('Ciudad')->default(fn($record) => $record?->buyer?->ciudad),
                                            Forms\Components\TextInput::make('comprador_estado')->label('Estado')->default(fn($record) => $record?->buyer?->estado),
                                            Forms\Components\TextInput::make('comprador_cp')->label('C.P.')->default(fn($record) => $record?->buyer?->cp),
                                        ]),
                                    ]),

                                // SECCIÓN DE COMPRADORES ADICIONALES 
                                Forms\Components\Section::make('Compradores Adicionales')
                                    ->description('Agregue aquí si es una compra conyugal (matrimonio) o hay copropietarios.')
                                    ->schema([
                                        Forms\Components\Repeater::make('compradores_adicionales')
                                            ->label('Agregar Comprador (+)')
                                            ->itemLabel(fn (array $state): ?string => trim(($state['nombres'] ?? '') . ' ' . ($state['ap_paterno'] ?? '')) ?: null)
                                            ->schema([
                                                Forms\Components\Grid::make(3)->schema([
                                                    Forms\Components\TextInput::make('nombres')->label('Nombre(s)')->required(),
                                                    Forms\Components\TextInput::make('ap_paterno')->label('Apellido Paterno')->required(),
                                                    Forms\Components\TextInput::make('ap_materno')->label('Apellido Materno'),
                                                ]),
                                                Forms\Components\Grid::make(2)->schema([
                                                    Forms\Components\TextInput::make('telefono')->tel()->label('Teléfono Fijo'),
                                                    Forms\Components\TextInput::make('celular')->tel()->label('Celular'),
                                                    Forms\Components\TextInput::make('email')->email()->label('Email'),
                                                    Forms\Components\DatePicker::make('fecha_nacimiento')->label('Fecha Nacimiento'),
                                                    Forms\Components\TextInput::make('rfc')->label('RFC'),
                                                    Forms\Components\TextInput::make('curp')->label('CURP'),
                                                ]),
                                                Forms\Components\Grid::make(2)->schema([
                                                    Forms\Components\Select::make('relacion')
                                                        ->label('Relación con el titular')
                                                        ->options([
                                                            'Esposo(a)' => 'Esposo(a)',
                                                            'Concubino(a)' => 'Concubino(a)',
                                                            'Padre/Madre' => 'Padre/Madre',
                                                            'Hijo(a)' => 'Hijo(a)',
                                                            'Socio' => 'Socio',
                                                            'Otro' => 'Otro (Especificar)',
                                                        ])
                                                        ->required()
                                                        ->live(),
                                                    Forms\Components\TextInput::make('otra_relacion')
                                                        ->label('Especifique relación')
                                                        ->visible(fn (Forms\Get $get) => $get('relacion') === 'Otro'),
                                                ]),
                                                
                                                Forms\Components\Toggle::make('mismo_domicilio')
                                                    ->label('¿Comparte el mismo domicilio que el comprador principal?')
                                                    ->onColor('success')
                                                    ->offColor('danger')
                                                    ->default(true)
                                                    ->live(),
                                                    
                                                Forms\Components\Group::make()
                                                    ->visible(fn (Forms\Get $get) => ! $get('mismo_domicilio'))
                                                    ->schema([
                                                        Forms\Components\Fieldset::make('Domicilio Particular')
                                                            ->schema([
                                                                Forms\Components\TextInput::make('calle')->label('Calle y Número')->required(),
                                                                Forms\Components\TextInput::make('colonia')->label('Colonia')->required(),
                                                                Forms\Components\TextInput::make('ciudad')->label('Ciudad'),
                                                                Forms\Components\TextInput::make('estado')->label('Estado'),
                                                                Forms\Components\TextInput::make('cp')->label('C.P.'),
                                                            ])
                                                    ]),
                                                    
                                                // DATOS ECONÓMICOS DEL COMPRADOR ADICIONAL 
                                                Forms\Components\Fieldset::make('Datos Económicos')
                                                    ->schema([
                                                        Forms\Components\Grid::make(2)->schema([
                                                            Forms\Components\Select::make('actividad')
                                                                ->label('Tipo de Actividad')
                                                                ->options([
                                                                    'Empleado' => 'Empleado',
                                                                    'Profesionista' => 'Profesionista',
                                                                    'Empresario' => 'Empresario',
                                                                    'Inversionista' => 'Inversionista',
                                                                    'Otro' => 'Otro',
                                                                ]),
                                                            Forms\Components\TextInput::make('empresa')->label('Empresa'),
                                                            Forms\Components\TextInput::make('ingresos')->numeric()->prefix('$')->label('Ingresos Mensuales'),
                                                            Forms\Components\TextInput::make('tipo_comprobacion')->label('Comprobación de ingresos'),
                                                        ]),
                                                        Forms\Components\Repeater::make('actividades_adicionales')
                                                            ->label('Agregar Actividad Extra (+)')
                                                            ->schema([
                                                                Forms\Components\TextInput::make('actividad')->label('Actividad Extra'),
                                                                Forms\Components\TextInput::make('ingresos')->numeric()->prefix('$')->label('Ingresos Extra'),
                                                            ])->columns(2)->collapsible()->collapsed(),
                                                    ]),
                                            ])->collapsible()->collapsed(),
                                    ]),

                                // DATOS ECONÓMICOS DEL COMPRADOR PRINCIPAL 
                                Forms\Components\Section::make('Datos Económicos')
                                    ->schema([
                                        Forms\Components\Select::make('comprador_actividad')
                                            ->options([
                                                'Empleado' => 'Empleado',
                                                'Profesionista' => 'Profesionista',
                                                'Empresario' => 'Empresario',
                                                'Inversionista' => 'Inversionista',
                                                'Otro' => 'Otro',
                                            ])->label('Tipo de Actividad'),
                                        Forms\Components\TextInput::make('comprador_empresa')->label('Empresa'),
                                        Forms\Components\TextInput::make('comprador_ingresos')->numeric()->prefix('$')->label('Ingresos Mensuales'),
                                        Forms\Components\TextInput::make('comprador_tipo_comprobacion')->label('Comprobación de ingresos'),
                                        Forms\Components\Repeater::make('comprador_actividades_adicionales')
                                            ->label('Agregar Actividad (+)')
                                            ->schema([
                                                Forms\Components\TextInput::make('actividad')->label('Actividad Extra'),
                                                Forms\Components\TextInput::make('ingresos')->numeric()->prefix('$')->label('Ingresos Extra'),
                                            ])->columns(2),
                                    ]),
                            ]),

                        // PESTAÑA 2: VENDEDOR
                        Forms\Components\Tabs\Tab::make('2. Vendedor')
                            ->icon('heroicon-o-home')
                            ->schema([
                                Forms\Components\Section::make('Datos del Propietario Principal')
                                    ->description('Información cargada automáticamente del perfil del vendedor.')
                                    ->schema([
                                        Forms\Components\Grid::make(3)->schema([
                                            Forms\Components\TextInput::make('vendedor_nombres')->label('Nombre(s)')->default(fn($record) => $record?->seller?->nombres),
                                            Forms\Components\TextInput::make('vendedor_ap_paterno')->label('Apellido Paterno')->default(fn($record) => $record?->seller?->ap_paterno),
                                            Forms\Components\TextInput::make('vendedor_ap_materno')->label('Apellido Materno')->default(fn($record) => $record?->seller?->ap_materno),
                                        ]),
                                        Forms\Components\Grid::make(2)->schema([
                                            Forms\Components\TextInput::make('vendedor_telefono')->tel()->label('Teléfono Fijo')->default(fn($record) => $record?->seller?->telefono),
                                            Forms\Components\TextInput::make('vendedor_celular')->tel()->label('Celular')->default(fn($record) => $record?->seller?->celular),
                                            Forms\Components\TextInput::make('vendedor_email')->email()->label('Email')->default(fn($record) => $record?->seller?->email),
                                        ]),
                                    ]),
                            ]),

                        // PESTAÑA 3: OPERACIÓN 
                        Forms\Components\Tabs\Tab::make('3. Operación')
                            ->icon('heroicon-o-clipboard-document-check')
                            ->schema([
                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\Select::make('estatus_operacion')
                                        ->options(['En búsqueda' => 'En búsqueda', 'Oferta aceptada' => 'Oferta aceptada', 'Contrato firmado' => 'Contrato firmado', 'Cerrada' => 'Cerrada', 'Cancelada' => 'Cancelada'])
                                        ->default('En búsqueda')->required()->label('Estatus'),
                                        
                                    Forms\Components\Select::make('momento_cobro_comision')
                                        ->label('¿Cuándo se cobra la comisión?')
                                        ->options([
                                            'a_la_venta' => 'A la firma del contrato',
                                            'a_la_escrituracion' => 'A la escrituración',
                                        ])
                                        ->default('a_la_venta')
                                        ->required(),
                                ]),
                                
                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('monto_operacion')->numeric()->prefix('$')->label('Precio Pactado')->live(onBlur: true)
                                        ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set) => $get('comision_porcentaje') ? $set('comision_monto', $get('monto_operacion') * ($get('comision_porcentaje') / 100)) : null),
                                    Forms\Components\TextInput::make('comision_porcentaje')->numeric()->suffix('%')->label('Comisión Agente (%)')->live(onBlur: true)
                                        ->afterStateUpdated(fn ($state, Forms\Get $get, Forms\Set $set) => $get('monto_operacion') ? $set('comision_monto', $get('monto_operacion') * ($state / 100)) : null),
                                    Forms\Components\TextInput::make('comision_monto')->numeric()->prefix('$')->label('Monto Comisión'),
                                ]),
                                Forms\Components\DatePicker::make('fecha_probable_cierre')->label('Fecha Probable Cierre'),
                            ]),

                        // PESTAÑA 4: HIPOTECA 
                        Forms\Components\Tabs\Tab::make('4. Hipoteca')
                            ->icon('heroicon-o-building-library')
                            ->schema([
                                Forms\Components\Toggle::make('requiere_hipoteca')->label('¿El cliente requiere hipoteca?')->onColor('success')->live(),
                                Forms\Components\Group::make()->visible(fn (Forms\Get $get) => $get('requiere_hipoteca'))->schema([
                                    Forms\Components\Select::make('estatus_hipoteca')->options(['Ingresada a Bancos' => 'Ingresada a Bancos', 'Aprobada' => 'Aprobada', 'Rechazada' => 'Rechazada', 'Firmada' => 'Firmada'])->label('Estatus Hipoteca'),
                                    Forms\Components\Section::make('Banco Principal')->schema([
                                        Forms\Components\TextInput::make('hipoteca_banco')->label('Banco'),
                                        Forms\Components\TextInput::make('hipoteca_monto_aprobado')->numeric()->prefix('$')->label('Monto Aprobado'),
                                    ])->columns(2),
                                ]),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                /** @var \App\Models\User $user */
                $user = auth()->user();
                if (! $user->hasRole('Administrador')) {
                    $query->where('user_id', $user->id);
                }
                return $query;
            })

            ->columns([
                TextColumn::make('fecha_inicio')
                    ->date('d/m/Y')
                    ->label('Fecha')
                    ->sortable(),

                // BÚSQUEDA ROBUSTA DE CLIENTE
                TextColumn::make('nombre_cliente_principal')
                    ->label('Cliente Comprador')
                    ->state(function (Sale $record) {
                        return $record->comprador_nombres . ' ' . $record->comprador_ap_paterno;
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('comprador_nombres', 'like', "%{$search}%")
                                     ->orWhere('comprador_ap_paterno', 'like', "%{$search}%")
                                     ->orWhere('comprador_ap_materno', 'like', "%{$search}%");
                    }),

                TextColumn::make('monto_operacion')
                    ->money('MXN')
                    ->label('Monto')
                    ->sortable(),

                TextColumn::make('estatus_operacion')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Cerrada' => 'success',
                        'Cancelada' => 'danger',
                        'En búsqueda' => 'warning',
                        'Apartado' => 'info',
                        default => 'gray',
                    })
                    ->searchable(),

                TextColumn::make('estatus_hipoteca')
                    ->label('Estatus Hipoteca')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Aprobada' => 'success',
                        'En trámite' => 'warning',
                        'Rechazada' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('tipo_inmueble')
                    ->label('Inmueble')
                    ->searchable()
                    ->sortable(),

                // COLUMNA AGENTE (Visible solo para Admin)
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Agente')
                    ->icon('heroicon-o-user')
                    ->sortable()
                    ->toggleable()
                    ->visible(fn () => auth()->user()->hasRole('Administrador')),
            ])
            ->filters([
                // 1. ESTATUS OPERACIÓN
                SelectFilter::make('estatus_operacion')
                    ->label('Estatus Operación')
                    ->multiple()
                    ->options([
                        'En búsqueda' => 'En búsqueda',
                        'Apartado' => 'Apartado',
                        'Cerrada' => 'Cerrada',
                        'Cancelada' => 'Cancelada',
                    ]),

                // 2. ESTATUS HIPOTECA
                SelectFilter::make('estatus_hipoteca')
                    ->label('Estatus Hipoteca')
                    ->multiple()
                    ->options([
                        'Recopila Documentos' => 'Recopila Documentos',
                        'Ingresada a Bancos' => 'Ingresada a Bancos',
                        'Aprobada' => 'Aprobada',
                        'Rechazada' => 'Rechazada',
                        'Avalúo' => 'Avalúo',
                        'Notaria' => 'Notaria',
                        'Programación a firma' => 'Programación a firma',
                        'Firmada' => 'Firmada',
                    ]),

                // 3. TIPO INMUEBLE
                SelectFilter::make('tipo_inmueble')
                    ->label('Tipo Inmueble')
                    ->options([
                        'Casa' => 'Casa',
                        'Departamento' => 'Departamento',
                        'Terreno' => 'Terreno',
                        'Local' => 'Local Comercial',
                        'Bodega' => 'Bodega',
                    ]),

                // 4. RANGO DE FECHAS
                Filter::make('fecha_inicio')
                    ->label('Fecha de Inicio')
                    ->form([
                        DatePicker::make('fecha_from')->label('Desde'),
                        DatePicker::make('fecha_until')->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['fecha_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('fecha_inicio', '>=', $date),
                            )
                            ->when(
                                $data['fecha_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('fecha_inicio', '<=', $date),
                            );
                    }),
                // --- FILTROS EXCLUSIVOS DE ADMINISTRADOR ---

                // 1. FILTRO POR AGENTE (User)
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Filtrar por Agente')
                    ->relationship('user', 'name') 
                    ->searchable()
                    ->preload()
                    ->visible(fn () => auth()->user()->hasRole('Administrador')),

                // 2. FILTRO POR OFICINA
                // (Agente pertenece a una Oficina)
                Tables\Filters\SelectFilter::make('oficina')
                    ->label('Filtrar por Oficina')
                    ->options(function () {
                        if (class_exists(\App\Models\Office::class)) {
                            return \App\Models\Office::pluck('nombre', 'id')->toArray();
                        }
                        return [];
                    })
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) return $query;
                        
                        // Filtramos las ventas donde el USUARIO pertenezca a la OFICINA seleccionada
                        return $query->whereHas('user', function ($q) use ($data) {
                            $q->where('office_id', $data['value']);
                        });
                    })
                    ->visible(fn () => auth()->user()->hasRole('Administrador')),
            ])
            ->filtersTriggerAction(
                fn (Actions\Action $action) => $action
                    ->button()
                    ->label('Filtros Avanzados')
                    ->slideOver(),
            )
            ->actions([
                Actions\EditAction::make()
                    ->iconButton()
                    ->tooltip('Editar Proceso de Venta'),
                Actions\DeleteAction::make()
                    ->iconButton()
                    ->tooltip('Borrar'),
            ])
            ->actionsColumnLabel('ACCIONES')
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('fecha_inicio', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSales::route('/'),
            'create' => Pages\CreateSale::route('/create'),
            'edit' => Pages\EditSale::route('/{record}/edit'),
        ];
    }
}