<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PropertyResource\Pages;
use App\Models\Property;
use App\Models\Owner;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PropertyResource extends Resource
{
    protected static ?string $model = Property::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationLabel = 'Propiedades';
    protected static ?string $navigationGroup = 'Rentas';
    protected static ?int $navigationSort = 4;
    protected static ?string $modelLabel = 'Propiedad';
    protected static ?string $pluralModelLabel = 'Propiedades';

    public static function getCluster(): ?string
    {
        return null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información de la Propiedad')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Propietario')
                            ->relationship('user', 'name', modifyQueryUsing: fn (Builder $query) => $query->where('is_owner', true))
                            ->getOptionLabelFromRecordUsing(fn (User $record) => $record->name . ' (' . $record->email . ')')
                            ->searchable(['name', 'email'])
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                if ($state) {
                                    $user = User::find($state);
                                    if ($user && $user->is_owner) {
                                        $owner = Owner::where('user_id', $user->id)->first();
                                        if ($owner) {
                                            // Pre-llenar campos de inmueble desde Owner si existen
                                            // Por ahora no hay campos de inmueble en Owner, pero se puede agregar después
                                        }
                                    }
                                }
                            }),

                        Forms\Components\Placeholder::make('folio')
                            ->label('Folio')
                            ->content(fn ($record) => $record?->folio ?? 'Se generará automáticamente')
                            ->visible(fn ($livewire) => $livewire instanceof Pages\EditProperty),

                        Forms\Components\Select::make('estatus')
                            ->label('Estatus')
                            ->options([
                                'disponible' => 'Disponible',
                                'rentada' => 'Rentada',
                                'inactiva' => 'Inactiva',
                            ])
                            ->required()
                            ->default('disponible'),
                    ])
                    ->columns(2),

                // SECCIÓN: DATOS DEL INMUEBLE
                Forms\Components\Section::make('Datos del Inmueble')
                    ->description('Información acerca del inmueble')
                    ->schema(self::getDatosInmuebleSchema())
                    ->columns(2)
                    ->collapsible(),

                // SECCIÓN: DIRECCIÓN DEL INMUEBLE
                Forms\Components\Section::make('Dirección del Inmueble')
                    ->schema(self::getDireccionInmuebleSchema())
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('folio')
                    ->label('Folio')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Folio copiado')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Propietario')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('tipo_inmueble')
                    ->label('Tipo')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('calle')
                    ->label('Dirección')
                    ->formatStateUsing(fn ($record) => 
                        ($record->calle ?? '') . ' ' . 
                        ($record->numero_exterior ?? '') . ' ' . 
                        ($record->colonia ?? '')
                    )
                    ->searchable(['calle', 'colonia'])
                    ->limit(30),

                Tables\Columns\TextColumn::make('precio_renta')
                    ->label('Precio Renta')
                    ->money('MXN')
                    ->sortable(),

                Tables\Columns\TextColumn::make('estatus')
                    ->label('Estatus')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'disponible' => 'success',
                        'rentada' => 'warning',
                        'inactiva' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'disponible' => 'Disponible',
                        'rentada' => 'Rentada',
                        'inactiva' => 'Inactiva',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de Creación')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Propietario')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('tipo_inmueble')
                    ->label('Tipo de Inmueble')
                    ->options([
                        'Casa' => 'Casa',
                        'Departamento' => 'Departamento',
                        'Local comercial' => 'Local comercial',
                        'Oficina' => 'Oficina',
                        'Bodega' => 'Bodega',
                        'Nave industrial' => 'Nave industrial',
                        'Consultorio' => 'Consultorio',
                        'Terreno' => 'Terreno',
                    ]),

                Tables\Filters\SelectFilter::make('estatus')
                    ->label('Estatus')
                    ->options([
                        'disponible' => 'Disponible',
                        'rentada' => 'Rentada',
                        'inactiva' => 'Inactiva',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProperties::route('/'),
            'create' => Pages\CreateProperty::route('/create'),
            'edit' => Pages\EditProperty::route('/{record}/edit'),
        ];
    }

    protected static function getDatosInmuebleSchema(): array
    {
        return [
            Forms\Components\Select::make('tipo_inmueble')
                ->label('Tipo de inmueble')
                ->options([
                    'Casa' => 'Casa',
                    'Departamento' => 'Departamento',
                    'Local comercial' => 'Local comercial',
                    'Oficina' => 'Oficina',
                    'Bodega' => 'Bodega',
                    'Nave industrial' => 'Nave industrial',
                    'Consultorio' => 'Consultorio',
                    'Terreno' => 'Terreno',
                ])
                ->required(),

            Forms\Components\Select::make('uso_suelo')
                ->label('Uso de suelo')
                ->options([
                    'Habitacional' => 'Habitacional',
                    'Comercial' => 'Comercial',
                    'Industrial' => 'Industrial',
                ])
                ->required(),

            Forms\Components\Select::make('mascotas')
                ->label('¿Mascotas?')
                ->options([
                    'si' => 'Sí',
                    'no' => 'No',
                ])
                ->required()
                ->live(),

            Forms\Components\TextInput::make('mascotas_especifica')
                ->label('Especifique')
                ->required(fn (Forms\Get $get) => $get('mascotas') === 'si')
                ->visible(fn (Forms\Get $get) => $get('mascotas') === 'si'),

            Forms\Components\TextInput::make('precio_renta')
                ->label('Precio de renta')
                ->numeric()
                ->prefix('$')
                ->required(),

            Forms\Components\Select::make('iva_renta')
                ->label('IVA en la renta')
                ->options([
                    'IVA incluido' => 'IVA incluido',
                    'Mas IVA' => 'Más IVA',
                    'Sin IVA' => 'Sin IVA',
                ])
                ->required(),

            Forms\Components\Select::make('frecuencia_pago')
                ->label('Frecuencia del pago de renta')
                ->options([
                    'Mensual' => 'Mensual',
                    'Semanal' => 'Semanal',
                    'Quincenal' => 'Quincenal',
                    'Semestral' => 'Semestral',
                    'Anual' => 'Anual',
                    'Otra' => 'Otra',
                ])
                ->required()
                ->live(),

            Forms\Components\TextInput::make('frecuencia_pago_otra')
                ->label('En caso de otra frecuencia')
                ->required(fn (Forms\Get $get) => $get('frecuencia_pago') === 'Otra')
                ->visible(fn (Forms\Get $get) => $get('frecuencia_pago') === 'Otra'),

            Forms\Components\Textarea::make('condiciones_pago')
                ->label('Condiciones de pago')
                ->rows(3)
                ->required()
                ->columnSpanFull(),

            Forms\Components\TextInput::make('deposito_garantia')
                ->label('Cantidad de depósito en garantía')
                ->numeric()
                ->prefix('$')
                ->required(),

            Forms\Components\Select::make('paga_mantenimiento')
                ->label('¿Se paga mantenimiento?')
                ->options([
                    'si' => 'Sí',
                    'no' => 'No',
                ])
                ->required()
                ->live(),

            Forms\Components\Select::make('quien_paga_mantenimiento')
                ->label('¿Quién paga el mantenimiento?')
                ->options([
                    'Arrendatario' => 'Arrendatario',
                    'Arrendador' => 'Arrendador',
                ])
                ->required(fn (Forms\Get $get) => $get('paga_mantenimiento') === 'si')
                ->visible(fn (Forms\Get $get) => $get('paga_mantenimiento') === 'si'),

            Forms\Components\Select::make('mantenimiento_incluido_renta')
                ->label('¿Está incluido en la renta?')
                ->options([
                    'si' => 'Sí',
                    'no' => 'No',
                ])
                ->required(fn (Forms\Get $get) => $get('paga_mantenimiento') === 'si')
                ->visible(fn (Forms\Get $get) => $get('paga_mantenimiento') === 'si'),

            Forms\Components\TextInput::make('costo_mantenimiento_mensual')
                ->label('Costo mensual del mantenimiento')
                ->numeric()
                ->prefix('$')
                ->required(fn (Forms\Get $get) => $get('paga_mantenimiento') === 'si')
                ->visible(fn (Forms\Get $get) => $get('paga_mantenimiento') === 'si'),

            Forms\Components\Textarea::make('instrucciones_pago')
                ->label('Instrucciones de pago')
                ->rows(3)
                ->required()
                ->columnSpanFull(),

            Forms\Components\Select::make('requiere_seguro')
                ->label('¿Se requiere contratar seguro?')
                ->options([
                    'si' => 'Sí',
                    'no' => 'No',
                ])
                ->required()
                ->live(),

            Forms\Components\TextInput::make('cobertura_seguro')
                ->label('¿Qué cobertura tiene?')
                ->required(fn (Forms\Get $get) => $get('requiere_seguro') === 'si')
                ->visible(fn (Forms\Get $get) => $get('requiere_seguro') === 'si'),

            Forms\Components\TextInput::make('monto_cobertura_seguro')
                ->label('Monto que cubre el seguro')
                ->numeric()
                ->prefix('$')
                ->required(fn (Forms\Get $get) => $get('requiere_seguro') === 'si')
                ->visible(fn (Forms\Get $get) => $get('requiere_seguro') === 'si'),

            Forms\Components\Textarea::make('servicios_pagar')
                ->label('Servicios que se deberán pagar del inmueble')
                ->rows(3)
                ->required()
                ->columnSpanFull(),
        ];
    }

    protected static function getDireccionInmuebleSchema(): array
    {
        return [
            Forms\Components\TextInput::make('calle')
                ->label('Calle')
                ->required()
                ->columnSpanFull(),

            Forms\Components\TextInput::make('numero_exterior')
                ->label('Número exterior')
                ->required(),

            Forms\Components\TextInput::make('numero_interior')
                ->label('Número interior'),

            Forms\Components\TextInput::make('codigo_postal')
                ->label('Código postal')
                ->required()
                ->maxLength(5),

            Forms\Components\TextInput::make('colonia')
                ->label('Colonia')
                ->required(),

            Forms\Components\TextInput::make('delegacion_municipio')
                ->label('Delegación / Municipio')
                ->required(),

            Forms\Components\Select::make('estado')
                ->label('Estado')
                ->options(\App\Helpers\EstadosMexico::getEstados())
                ->required()
                ->searchable(),

            Forms\Components\Textarea::make('referencias_ubicacion')
                ->label('Referencias para ubicar el domicilio')
                ->rows(3)
                ->required()
                ->columnSpanFull(),

            Forms\Components\Textarea::make('inventario')
                ->label('Inventario del inmueble')
                ->rows(3)
                ->required()
                ->columnSpanFull()
                ->helperText('Describa el inventario con el que cuenta el inmueble, por ejemplo, cortinas, muebles, hidroneumático, etc.'),
        ];
    }
}
