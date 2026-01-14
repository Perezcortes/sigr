<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SaleResource\Pages;
use App\Models\Sale;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Fieldset;

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
            ->columns([
                Tables\Columns\TextColumn::make('fecha_inicio')
                    ->date('d/m/Y')
                    ->label('Fecha')
                    ->sortable(),
                Tables\Columns\TextColumn::make('nombre_cliente_principal') // Podemos concatenar nombre comprador
                    ->state(function (Sale $record) {
                        return $record->comprador_nombres . ' ' . $record->comprador_ap_paterno;
                    })
                    ->label('Cliente'),
                Tables\Columns\TextColumn::make('monto_operacion')
                    ->money('MXN')
                    ->label('Monto'),
                Tables\Columns\TextColumn::make('estatus_operacion')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Cerrada' => 'success',
                        'Cancelada' => 'danger',
                        'En búsqueda' => 'warning',
                        default => 'info',
                    }),
                Tables\Columns\TextColumn::make('estatus_hipoteca')
                    ->label('Estatus Hipoteca')
                    ->badge(),
                Tables\Columns\TextColumn::make('tipo_inmueble'),
            ])
            ->filters([
                // Filtros opcionales
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                     ->iconButton()
                     ->tooltip('Editar Proceso de Venta'),
            ])
            ->actionsColumnLabel('ACCIONES')
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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