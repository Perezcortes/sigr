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
                Forms\Components\Tabs::make('Proceso de Venta')
                    ->tabs([
                        // PESTAÑA 1: COMPRADOR
                        Forms\Components\Tabs\Tab::make('1. Comprador')
                            ->icon('heroicon-o-user')
                            ->schema([
                                Forms\Components\Section::make('Datos Generales')
                                    ->schema([
                                        Forms\Components\Grid::make(3)
                                            ->schema([
                                                Forms\Components\TextInput::make('comprador_nombres')->label('Nombre(s)')->required(),
                                                Forms\Components\TextInput::make('comprador_ap_paterno')->label('Apellido Paterno')->required(),
                                                Forms\Components\TextInput::make('comprador_ap_materno')->label('Apellido Materno'),
                                            ]),
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('comprador_telefono')->tel()->label('Teléfono Fijo'),
                                                Forms\Components\TextInput::make('comprador_celular')->tel()->label('Celular'),
                                                Forms\Components\TextInput::make('comprador_email')->email()->label('Email'),
                                                Forms\Components\DatePicker::make('comprador_fecha_nacimiento')->label('Fecha Nacimiento'),
                                                Forms\Components\TextInput::make('comprador_rfc')->label('RFC'),
                                                Forms\Components\TextInput::make('comprador_curp')->label('CURP'),
                                            ]),
                                        Forms\Components\Fieldset::make('Dirección')
                                            ->schema([
                                                Forms\Components\TextInput::make('comprador_calle')->label('Calle y Número'),
                                                Forms\Components\TextInput::make('comprador_colonia')->label('Colonia'),
                                                Forms\Components\TextInput::make('comprador_ciudad')->label('Ciudad'),
                                                Forms\Components\TextInput::make('comprador_estado')->label('Estado'),
                                                Forms\Components\TextInput::make('comprador_cp')->label('C.P.'),
                                            ]),
                                        
                                        // REPEATER: Compradores Adicionales
                                        Forms\Components\Repeater::make('compradores_adicionales')
                                            ->label('Compradores Adicionales (+)')
                                            ->schema([
                                                Forms\Components\TextInput::make('nombre_completo')->label('Nombre Completo'),
                                                Forms\Components\TextInput::make('relacion')->label('Relación con primer comprador'),
                                            ])
                                            ->columns(2)
                                            ->collapsible(),
                                    ]),

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

                                        // REPEATER: Actividad Adicional
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
                                Forms\Components\Section::make('Datos del Propietario/Vendedor')
                                    ->schema([
                                        Forms\Components\Grid::make(3)
                                            ->schema([
                                                Forms\Components\TextInput::make('vendedor_nombres')->label('Nombre(s)'),
                                                Forms\Components\TextInput::make('vendedor_ap_paterno')->label('Apellido Paterno'),
                                                Forms\Components\TextInput::make('vendedor_ap_materno')->label('Apellido Materno'),
                                            ]),
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('vendedor_telefono')->tel()->label('Teléfono'),
                                                Forms\Components\TextInput::make('vendedor_celular')->tel()->label('Celular'),
                                                Forms\Components\TextInput::make('vendedor_email')->email()->label('Email'),
                                                Forms\Components\DatePicker::make('vendedor_fecha_nacimiento')->label('Fecha Nacimiento'),
                                                Forms\Components\TextInput::make('vendedor_rfc')->label('RFC'),
                                                Forms\Components\TextInput::make('vendedor_curp')->label('CURP'),
                                            ]),
                                        Forms\Components\fieldset::make('Dirección')
                                            ->schema([
                                                Forms\Components\TextInput::make('vendedor_calle')->label('Calle y Número'),
                                                Forms\Components\TextInput::make('vendedor_colonia')->label('Colonia'),
                                                Forms\Components\TextInput::make('vendedor_ciudad')->label('Ciudad'),
                                                Forms\Components\TextInput::make('vendedor_estado')->label('Estado'),
                                                Forms\Components\TextInput::make('vendedor_cp')->label('C.P.'),
                                            ]),

                                        // REPEATER: Vendedores Adicionales
                                        Forms\Components\Repeater::make('vendedores_adicionales')
                                            ->label('Vendedores Adicionales (+)')
                                            ->schema([
                                                Forms\Components\TextInput::make('nombre_completo')->label('Nombre Completo'),
                                                Forms\Components\TextInput::make('relacion')->label('Relación con primer vendedor'),
                                            ])
                                            ->columns(2)
                                            ->collapsible(),
                                    ]),
                            ]),

                        // PESTAÑA 3: OPERACIÓN
                        Forms\Components\Tabs\Tab::make('3. Operación')
                            ->icon('heroicon-o-clipboard-document-check')
                            ->schema([
                                Forms\Components\Select::make('estatus_operacion')
                                    ->options([
                                        'En búsqueda' => 'En búsqueda',
                                        'Oferta aceptada' => 'Oferta aceptada',
                                        'Contrato firmado' => 'Contrato firmado',
                                        'Formalización' => 'Formalización',
                                        'Cerrada' => 'Cerrada',
                                        'Cancelada' => 'Cancelada',
                                    ])
                                    ->default('En búsqueda')
                                    ->required()
                                    ->label('Estatus'),

                                Forms\Components\TextInput::make('tipo_inmueble')
                                    ->label('Tipo de Inmueble'),

                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('precio_lista')
                                        ->numeric()
                                        ->prefix('$')
                                        ->label('Precio de Lista'),

                                    Forms\Components\TextInput::make('monto_operacion') // Es el precio pactado
                                        ->numeric()
                                        ->prefix('$')
                                        ->label('Precio Pactado')
                                        ->live(onBlur: true) // Escuchar cambios para calcular comisión
                                        ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                                            // Recalcular si cambia el precio
                                            $precio = (float) $get('monto_operacion');
                                            $porcentaje = (float) $get('comision_porcentaje');
                                            if ($precio && $porcentaje) {
                                                $set('comision_monto', $precio * ($porcentaje / 100));
                                            }
                                        }),
                                ]),

                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('comision_porcentaje')
                                        ->numeric()
                                        ->suffix('%')
                                        ->label('Comisión Agente (%)')
                                        ->live(onBlur: true) // Escuchar cambios
                                        ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                            // CÁLCULO AUTOMÁTICO DE COMISIÓN
                                            $precio = (float) $get('monto_operacion');
                                            if ($precio && $state) {
                                                $set('comision_monto', $precio * ($state / 100));
                                            }
                                        }),

                                    Forms\Components\TextInput::make('comision_monto')
                                        ->numeric()
                                        ->prefix('$')
                                        ->label('Monto Comisión'), // Es editable aunque se calcule
                                ]),

                                Forms\Components\Select::make('momento_pago_comision')
                                    ->options([
                                        'Al firmar contrato' => 'Al firmar contrato',
                                        'Al escriturar' => 'Al escriturar',
                                        'Mitad y Mitad' => 'Mitad y Mitad',
                                    ])->label('Pago de comisión'),

                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('notaria_numero')->label('No. Notaría'),
                                    Forms\Components\TextInput::make('notaria_titular')->label('Titular Notaría'),
                                ]),

                                Forms\Components\DatePicker::make('fecha_probable_cierre')->label('Fecha Probable Cierre'),

                                // LOG DE COMENTARIOS
                                Forms\Components\Repeater::make('bitacora_operacion')
                                    ->label('Bitácora / Comentarios')
                                    ->schema([
                                        Forms\Components\Textarea::make('comentario')->required(),
                                        Forms\Components\DateTimePicker::make('fecha')
                                            ->default(now())
                                            ->disabled(),
                                        Forms\Components\TextInput::make('autor')
                                            ->default(fn () => auth()->user()->name)
                                            ->disabled(),
                                    ])
                                    ->columns(1)
                                    ->collapsible()
                                    ->collapsed(),
                            ]),

                        // PESTAÑA 4: HIPOTECA
                        Forms\Components\Tabs\Tab::make('4. Hipoteca')
                            ->icon('heroicon-o-building-library')
                            ->schema([
                                Forms\Components\Toggle::make('requiere_hipoteca')
                                    ->label('¿El cliente requiere hipoteca?')
                                    ->onColor('success')
                                    ->live(), // Hace reactivo el formulario

                                Forms\Components\Group::make()
                                    ->visible(fn (Forms\Get $get) => $get('requiere_hipoteca')) // Solo visible si el toggle es TRUE
                                    ->schema([
                                        Forms\Components\Select::make('estatus_hipoteca')
                                            ->options([
                                                'Recopila Documentos' => 'Recopila Documentos',
                                                'Ingresada a Bancos' => 'Ingresada a Bancos',
                                                'Aprobada' => 'Aprobada',
                                                'Rechazada' => 'Rechazada',
                                                'Avalúo' => 'Avalúo',
                                                'Notaria' => 'Notaria',
                                                'Programación a firma' => 'Programación a firma',
                                                'Firmada' => 'Firmada',
                                            ])->label('Estatus Hipoteca'),

                                        Forms\Components\TextInput::make('hipoteca_broker')->label('Broker Hipotecario'),
                                        
                                        Forms\Components\Section::make('Banco Principal')
                                            ->schema([
                                                Forms\Components\TextInput::make('hipoteca_banco')->label('Banco'),
                                                Forms\Components\TextInput::make('hipoteca_ejecutivo_nombre')->label('Ejecutivo (Nombre)'),
                                                Forms\Components\TextInput::make('hipoteca_ejecutivo_telefono')->label('Teléfono'),
                                                Forms\Components\TextInput::make('hipoteca_ejecutivo_email')->email()->label('Email'),
                                            ])->columns(2),

                                        Forms\Components\TextInput::make('hipoteca_monto_aprobado')
                                            ->numeric()
                                            ->prefix('$')
                                            ->label('Monto Aprobado'),
                                        
                                        Forms\Components\Textarea::make('hipoteca_comentarios')->label('Comentarios Generales'),

                                        // REPEATER: Bancos Adicionales
                                        Forms\Components\Repeater::make('hipoteca_bancos_adicionales')
                                            ->label('Agregar Banco Adicional (+)')
                                            ->schema([
                                                Forms\Components\TextInput::make('banco')->label('Banco'),
                                                Forms\Components\TextInput::make('monto')->numeric()->label('Monto'),
                                            ])->columns(2),
                                    ]),
                            ]),
                    ])->columnSpanFull(), // Las pestañas ocupan todo el ancho
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
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