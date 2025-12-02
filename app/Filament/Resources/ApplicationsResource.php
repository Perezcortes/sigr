<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApplicationsResource\Pages;
use App\Models\Application;
use App\Models\Tenant;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ApplicationsResource extends Resource
{
    protected static ?string $model = Application::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Solicitudes';
    protected static ?string $navigationGroup = 'Rentas';
    protected static ?int $navigationSort = 1;
    protected static ?string $modelLabel = 'Solicitud';
    protected static ?string $pluralModelLabel = 'Solicitudes';

    public static function getCluster(): ?string
    {
        return null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información de la Solicitud')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Tenant')
                            ->relationship('user', 'name', modifyQueryUsing: fn (Builder $query) => $query->where('is_tenant', true))
                            ->getOptionLabelFromRecordUsing(fn (User $record) => $record->name . ' (' . $record->email . ')')
                            ->searchable(['name', 'email'])
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                if ($state) {
                                    $user = User::find($state);
                                    if ($user && $user->is_tenant) {
                                        $tenant = Tenant::where('user_id', $user->id)->first();
                                        if ($tenant) {
                                            // Pre-llenar campos de empleo
                                            $set('profesion_oficio_puesto', $tenant->profesion_oficio_puesto);
                                            $set('tipo_empleo', $tenant->tipo_empleo);
                                            $set('telefono_empleo', $tenant->telefono_empleo);
                                            $set('extension_empleo', $tenant->extension_empleo);
                                            $set('empresa_trabaja', $tenant->empresa_trabaja);
                                            $set('calle_empleo', $tenant->calle_empleo);
                                            $set('numero_exterior_empleo', $tenant->numero_exterior_empleo);
                                            $set('numero_interior_empleo', $tenant->numero_interior_empleo);
                                            $set('codigo_postal_empleo', $tenant->codigo_postal_empleo);
                                            $set('colonia_empleo', $tenant->colonia_empleo);
                                            $set('delegacion_municipio_empleo', $tenant->delegacion_municipio_empleo);
                                            $set('estado_empleo', $tenant->estado_empleo);
                                            $set('fecha_ingreso', $tenant->fecha_ingreso);
                                            $set('jefe_nombres', $tenant->jefe_nombres);
                                            $set('jefe_primer_apellido', $tenant->jefe_primer_apellido);
                                            $set('jefe_segundo_apellido', $tenant->jefe_segundo_apellido);
                                            $set('jefe_telefono', $tenant->jefe_telefono);
                                            $set('jefe_extension', $tenant->jefe_extension);
                                            
                                            // Pre-llenar campos de ingresos
                                            $set('ingreso_mensual_comprobable', $tenant->ingreso_mensual_comprobable);
                                            $set('ingreso_mensual_no_comprobable', $tenant->ingreso_mensual_no_comprobable);
                                            $set('numero_personas_dependen', $tenant->numero_personas_dependen);
                                            $set('otra_persona_aporta', $tenant->otra_persona_aporta);
                                            $set('numero_personas_aportan', $tenant->numero_personas_aportan);
                                            $set('persona_aporta_nombres', $tenant->persona_aporta_nombres);
                                            $set('persona_aporta_primer_apellido', $tenant->persona_aporta_primer_apellido);
                                            $set('persona_aporta_segundo_apellido', $tenant->persona_aporta_segundo_apellido);
                                            $set('persona_aporta_parentesco', $tenant->persona_aporta_parentesco);
                                            $set('persona_aporta_telefono', $tenant->persona_aporta_telefono);
                                            $set('persona_aporta_empresa', $tenant->persona_aporta_empresa);
                                            $set('persona_aporta_ingreso_comprobable', $tenant->persona_aporta_ingreso_comprobable);
                                            
                                            // Pre-llenar campos de uso de propiedad
                                            $set('tipo_inmueble_desea', $tenant->tipo_inmueble_desea);
                                            $set('giro_negocio', $tenant->giro_negocio);
                                            $set('experiencia_giro', $tenant->experiencia_giro);
                                            $set('propositos_arrendamiento', $tenant->propositos_arrendamiento);
                                            $set('sustituye_otro_domicilio', $tenant->sustituye_otro_domicilio);
                                            $set('domicilio_anterior_calle', $tenant->domicilio_anterior_calle);
                                            $set('domicilio_anterior_numero_exterior', $tenant->domicilio_anterior_numero_exterior);
                                            $set('domicilio_anterior_numero_interior', $tenant->domicilio_anterior_numero_interior);
                                            $set('domicilio_anterior_codigo_postal', $tenant->domicilio_anterior_codigo_postal);
                                            $set('domicilio_anterior_colonia', $tenant->domicilio_anterior_colonia);
                                            $set('domicilio_anterior_delegacion_municipio', $tenant->domicilio_anterior_delegacion_municipio);
                                            $set('domicilio_anterior_estado', $tenant->domicilio_anterior_estado);
                                            $set('motivo_cambio_domicilio', $tenant->motivo_cambio_domicilio);
                                        }
                                    }
                                }
                            }),

                        Forms\Components\Placeholder::make('folio')
                            ->label('Folio')
                            ->content(fn ($record) => $record?->folio ?? 'Se generará automáticamente')
                            ->visible(fn ($livewire) => $livewire instanceof Pages\EditApplications),

                        Forms\Components\Select::make('estatus')
                            ->label('Estatus')
                            ->options([
                                'pendiente' => 'Pendiente',
                                'en_revision' => 'En Revisión',
                                'activa' => 'Activa',
                                'aprobada' => 'Aprobada',
                                'rechazada' => 'Rechazada',
                                'vencida' => 'Vencida',
                            ])
                            ->required()
                            ->default('pendiente'),
                    ])
                    ->columns(2),

                // SECCIÓN: DATOS DE EMPLEO E INGRESOS
                Forms\Components\Section::make('Datos de Empleo e Ingresos')
                    ->description('Información sobre el empleo y situación económica')
                    ->schema(self::getDatosEmpleoSchema())
                    ->columns(2)
                    ->collapsible(),

                // SECCIÓN: USO DE PROPIEDAD
                Forms\Components\Section::make('Uso de Propiedad')
                    ->description('Información sobre el uso que dará al inmueble')
                    ->schema(self::getUsoPropiedadSchema())
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
                    ->label('Tenant')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('estatus')
                    ->label('Estatus')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pendiente' => 'warning',
                        'en_revision' => 'info',
                        'activa' => 'success',
                        'aprobada' => 'success',
                        'rechazada' => 'danger',
                        'vencida' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pendiente' => 'Pendiente',
                        'en_revision' => 'En Revisión',
                        'activa' => 'Activa',
                        'aprobada' => 'Aprobada',
                        'rechazada' => 'Rechazada',
                        'vencida' => 'Vencida',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de Creación')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Tenant')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('estatus')
                    ->label('Estatus')
                    ->options([
                        'pendiente' => 'Pendiente',
                        'en_revision' => 'En Revisión',
                        'activa' => 'Activa',
                        'aprobada' => 'Aprobada',
                        'rechazada' => 'Rechazada',
                        'vencida' => 'Vencida',
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
            'index' => Pages\ListApplications::route('/'),
            'create' => Pages\CreateApplications::route('/create'),
            'edit' => Pages\EditApplications::route('/{record}/edit'),
        ];
    }

    protected static function getDatosEmpleoSchema(): array
    {
        return [
            // Información sobre el empleo
            Forms\Components\Group::make()
                ->schema([
                    Forms\Components\Placeholder::make('empleo_info')
                        ->label('Información sobre el empleo')
                        ->content('Complete la información de su situación laboral')
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('profesion_oficio_puesto')
                        ->label('Profesión, oficio o puesto')
                        ->required(),

                    Forms\Components\Select::make('tipo_empleo')
                        ->label('Tipo de empleo')
                        ->options([
                            'Dueño de negocio' => 'Dueño de negocio',
                            'Empresario' => 'Empresario',
                            'Independiente' => 'Independiente',
                            'Empleado' => 'Empleado',
                            'Comisionista' => 'Comisionista',
                            'Jubilado' => 'Jubilado',
                        ])
                        ->required(),

                    Forms\Components\TextInput::make('telefono_empleo')
                        ->label('Teléfono')
                        ->tel()
                        ->required(),

                    Forms\Components\TextInput::make('extension_empleo')
                        ->label('No. de extensión'),

                    Forms\Components\TextInput::make('empresa_trabaja')
                        ->label('Empresa donde trabaja')
                        ->required()
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('calle_empleo')
                        ->label('Calle')
                        ->required()
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('numero_exterior_empleo')
                        ->label('Número exterior')
                        ->required(),

                    Forms\Components\TextInput::make('numero_interior_empleo')
                        ->label('Número interior'),

                    Forms\Components\TextInput::make('codigo_postal_empleo')
                        ->label('Código postal')
                        ->required()
                        ->maxLength(5),

                    Forms\Components\TextInput::make('colonia_empleo')
                        ->label('Colonia')
                        ->required(),

                    Forms\Components\TextInput::make('delegacion_municipio_empleo')
                        ->label('Delegación / Municipio')
                        ->required(),

                    Forms\Components\Select::make('estado_empleo')
                        ->label('Estado')
                        ->options(\App\Helpers\EstadosMexico::getEstados())
                        ->required()
                        ->searchable(),

                    Forms\Components\DatePicker::make('fecha_ingreso')
                        ->label('Fecha de Ingreso')
                        ->displayFormat('d/m/Y')
                        ->required()
                        ->native(false),
                ])
                ->columns(2)
                ->columnSpanFull(),

            // Jefe inmediato
            Forms\Components\Group::make()
                ->schema([
                    Forms\Components\Placeholder::make('jefe_info')
                        ->label('Jefe inmediato')
                        ->content('Complete la información de su jefe inmediato')
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('jefe_nombres')
                        ->label('Nombre (s)')
                        ->required(),

                    Forms\Components\TextInput::make('jefe_primer_apellido')
                        ->label('Apellido Paterno')
                        ->required(),

                    Forms\Components\TextInput::make('jefe_segundo_apellido')
                        ->label('Apellido Materno'),

                    Forms\Components\TextInput::make('jefe_telefono')
                        ->label('Teléfono de oficina')
                        ->tel()
                        ->required(),

                    Forms\Components\TextInput::make('jefe_extension')
                        ->label('Número de extensión'),
                ])
                ->columns(2)
                ->columnSpanFull(),

            // Ingresos
            Forms\Components\Group::make()
                ->schema([
                    Forms\Components\Placeholder::make('ingresos_info')
                        ->label('Ingresos')
                        ->content('Complete la información de sus ingresos')
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('ingreso_mensual_comprobable')
                        ->label('Ingreso mensual comprobable')
                        ->numeric()
                        ->prefix('$')
                        ->required(),

                    Forms\Components\TextInput::make('ingreso_mensual_no_comprobable')
                        ->label('Ingreso mensual no comprobable')
                        ->numeric()
                        ->prefix('$'),

                    Forms\Components\TextInput::make('numero_personas_dependen')
                        ->label('Número de personas que dependen de usted')
                        ->numeric()
                        ->minValue(0)
                        ->required()
                        ->columnSpanFull(),

                    Forms\Components\Radio::make('otra_persona_aporta')
                        ->label('¿Alguna otra persona aporta al ingreso familiar?')
                        ->options([
                            0 => 'No',
                            1 => 'Sí',
                        ])
                        ->required()
                        ->live()
                        ->columnSpanFull(),

                    // Información de persona que aporta (solo si es verdadero)
                    Forms\Components\Group::make()
                        ->schema([
                            Forms\Components\Placeholder::make('persona_aporta_info')
                                ->label('Información de la persona que aporta al ingreso familiar')
                                ->content('Complete la información de la persona que aporta')
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('numero_personas_aportan')
                                ->label('Número de personas que aportan al ingreso familiar')
                                ->numeric()
                                ->minValue(1)
                                ->required(),

                            Forms\Components\TextInput::make('persona_aporta_nombres')
                                ->label('Nombre (s)')
                                ->required(),

                            Forms\Components\TextInput::make('persona_aporta_primer_apellido')
                                ->label('Apellido Paterno')
                                ->required(),

                            Forms\Components\TextInput::make('persona_aporta_segundo_apellido')
                                ->label('Apellido Materno'),

                            Forms\Components\TextInput::make('persona_aporta_parentesco')
                                ->label('Parentesco')
                                ->required(),

                            Forms\Components\TextInput::make('persona_aporta_telefono')
                                ->label('Teléfono')
                                ->tel()
                                ->required(),

                            Forms\Components\TextInput::make('persona_aporta_empresa')
                                ->label('Empresa donde trabaja')
                                ->required(),

                            Forms\Components\TextInput::make('persona_aporta_ingreso_comprobable')
                                ->label('Ingreso mensual comprobable')
                                ->numeric()
                                ->prefix('$')
                                ->required(),
                        ])
                        ->columns(2)
                        ->visible(fn (Forms\Get $get) => $get('otra_persona_aporta') == 1)
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ];
    }

    protected static function getUsoPropiedadSchema(): array
    {
        return [
            Forms\Components\Group::make()
                ->schema([
                    Forms\Components\Placeholder::make('uso_comercial_info')
                        ->label('Datos del uso comercial')
                        ->content('Complete la información sobre el uso que dará al inmueble')
                        ->columnSpanFull(),

                    Forms\Components\Select::make('tipo_inmueble_desea')
                        ->label('Tipo de inmueble que desea rentar')
                        ->options([
                            'Local' => 'Local',
                            'Oficina' => 'Oficina',
                            'Consultorio' => 'Consultorio',
                            'Bodega' => 'Bodega',
                            'Nave Industrial' => 'Nave Industrial',
                        ])
                        ->required(),

                    Forms\Components\TextInput::make('giro_negocio')
                        ->label('¿Cuál es el giro de su negocio?')
                        ->required(),

                    Forms\Components\Textarea::make('experiencia_giro')
                        ->label('Describa brevemente su experiencia en el giro')
                        ->rows(3)
                        ->required()
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('propositos_arrendamiento')
                        ->label('Propósitos del arrendamiento')
                        ->rows(3)
                        ->required()
                        ->helperText('Establecer sucursal, oficina matriz, domicilio fiscal, centro de operaciones, almacén, punto de venta, etc.')
                        ->columnSpanFull(),

                    Forms\Components\Radio::make('sustituye_otro_domicilio')
                        ->label('¿Este inmueble sustituirá otro domicilio?')
                        ->options([
                            0 => 'No',
                            1 => 'Sí',
                        ])
                        ->required()
                        ->live()
                        ->columnSpanFull(),

                    // Información del domicilio anterior
                    Forms\Components\Group::make()
                        ->schema([
                            Forms\Components\Placeholder::make('domicilio_anterior_info')
                                ->label('Información del domicilio anterior')
                                ->content('Complete la información del domicilio anterior')
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('domicilio_anterior_calle')
                                ->label('Calle')
                                ->required()
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('domicilio_anterior_numero_exterior')
                                ->label('Número exterior')
                                ->required(),

                            Forms\Components\TextInput::make('domicilio_anterior_numero_interior')
                                ->label('Número interior'),

                            Forms\Components\TextInput::make('domicilio_anterior_codigo_postal')
                                ->label('Código postal')
                                ->required()
                                ->maxLength(5),

                            Forms\Components\TextInput::make('domicilio_anterior_colonia')
                                ->label('Colonia')
                                ->required(),

                            Forms\Components\TextInput::make('domicilio_anterior_delegacion_municipio')
                                ->label('Delegación / Municipio')
                                ->required(),

                            Forms\Components\Select::make('domicilio_anterior_estado')
                                ->label('Estado')
                                ->options(\App\Helpers\EstadosMexico::getEstados())
                                ->required()
                                ->searchable(),

                            Forms\Components\Textarea::make('motivo_cambio_domicilio')
                                ->label('Motivo del cambio de domicilio')
                                ->rows(3)
                                ->required()
                                ->columnSpanFull(),
                        ])
                        ->columns(2)
                        ->visible(fn (Forms\Get $get) => $get('sustituye_otro_domicilio') == 1)
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ];
    }
}
