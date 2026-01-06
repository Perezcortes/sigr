<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RentResource\Pages;
use App\Models\Rent;
use App\Models\Tenant;
use App\Models\Owner;
use App\Models\Application;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

class RentResource extends Resource
{
    protected static ?string $model = Rent::class;
    protected static ?string $navigationIcon = 'heroicon-o-home-modern';
    protected static ?string $navigationLabel = 'Mis rentas';
    protected static ?string $modelLabel = 'Renta';
    protected static ?string $pluralModelLabel = 'Mis rentas';
    protected static ?string $navigationGroup = 'Rentas';
    protected static ?int $navigationSort = 3;

    public static function getCluster(): ?string
    {
        return null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información de la Renta')
                    ->schema([
                        // Mostrar el folio (solo lectura si ya existe)
                        Forms\Components\Placeholder::make('folio')
                            ->label('Folio')
                            ->content(fn ($record) => $record?->folio ?? 'Se generará automáticamente')
                            ->visible(fn ($livewire) => $livewire instanceof Pages\EditRent || $livewire instanceof Pages\ViewRent),
                        
                        // Solo los campos esenciales
                        Select::make('tenant_id')
                            ->relationship('tenant', 'nombres')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->nombre_completo)
                            ->searchable(['nombres', 'primer_apellido', 'razon_social'])
                            ->preload()
                            ->required()
                            ->label('Seleccionar Inquilino')
                            ->createOptionForm(fn (Form $form) => self::getTenantCreationForm($form))
                            ->createOptionModalHeading('Crear Nuevo Inquilino')
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('application_id', null)),
                        
                        Select::make('application_id')
                            ->label('Solicitud Activa')
                            ->options(function (Forms\Get $get) {
                                $tenantId = $get('tenant_id');
                                if (!$tenantId) {
                                    return [];
                                }
                                
                                $tenant = Tenant::find($tenantId);
                                if (!$tenant || !$tenant->user_id) {
                                    return [];
                                }
                                
                                return Application::where('user_id', $tenant->user_id)
                                    ->where('estatus', 'activa')
                                    ->get()
                                    ->mapWithKeys(fn ($app) => [$app->id => $app->folio . ' - ' . ($app->user->name ?? '')])
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->label('Seleccionar Solicitud Activa')
                            ->helperText('Seleccione una solicitud activa del tenant para vincular los datos de empleo, ingresos y uso de propiedad')
                            ->visible(fn (Forms\Get $get) => !empty($get('tenant_id'))),
                        
                        Select::make('owner_id')
                            ->relationship('owner', 'nombres')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->nombre_completo)
                            ->searchable(['nombres', 'primer_apellido', 'razon_social'])
                            ->preload()
                            ->required()
                            ->label('Seleccionar Propietario')
                            ->createOptionForm(fn (Form $form) => self::getOwnerCreationForm($form))
                            ->createOptionModalHeading('Crear Nuevo Propietario'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['tenant', 'owner']))
            ->columns([
                TextColumn::make('folio')
                    ->label('Folio')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Folio copiado')
                    ->badge()
                    ->color('success'),

                TextColumn::make('tenant.nombre_completo')
                    ->label('Inquilino')
                    ->formatStateUsing(fn ($state, $record) => $record->tenant->nombre_completo ?? 'N/A')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('tenant', function ($q) use ($search) {
                            $q->where('nombres', 'like', "%{$search}%")
                              ->orWhere('primer_apellido', 'like', "%{$search}%")
                              ->orWhere('razon_social', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(),

                TextColumn::make('owner.nombre_completo')
                    ->label('Propietario')
                    ->formatStateUsing(fn ($state, $record) => $record->owner->nombre_completo ?? 'N/A')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('owner', function ($q) use ($search) {
                            $q->where('nombres', 'like', "%{$search}%")
                              ->orWhere('primer_apellido', 'like', "%{$search}%")
                              ->orWhere('razon_social', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(),

                TextColumn::make('estatus')
                    ->label('Estatus')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'nueva' => 'gray',
                        'documentacion' => 'warning',
                        'analisis' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'nueva' => 'Nueva',
                        'documentacion' => 'Documentación',
                        'analisis' => 'Análisis',
                        default => $state,
                    }),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tenant_id')
                    ->relationship('tenant', 'nombre_completo')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->nombre_completo)
                    ->searchable()
                    ->label('Filtrar por Inquilino'),

                Tables\Filters\SelectFilter::make('owner_id')
                    ->relationship('owner', 'nombre_completo')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->nombre_completo)
                    ->searchable()
                    ->label('Filtrar por Propietario'),

                Tables\Filters\SelectFilter::make('estatus')
                    ->label('Estatus')
                    ->options([
                        'nueva' => 'Nueva',
                        'documentacion' => 'Documentación',
                        'analisis' => 'Análisis',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn ($record) => static::getUrl('view', ['record' => $record->hash_id]))
                    ->iconButton() // Convierte el botón a solo icono
                    ->tooltip('Ver'), // (Opcional) Muestra texto al pasar el mouse
                Tables\Actions\EditAction::make()
                    ->url(fn ($record) => static::getUrl('edit', ['record' => $record->hash_id]))
                    ->iconButton() // Convierte el botón a solo icono
                    ->tooltip('Editar'),
                Tables\Actions\DeleteAction::make()
                    ->iconButton() // Convierte el botón a solo icono
                    ->tooltip('Borrar'),
            ])
            ->actionsColumnLabel('ACCIONES') 
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
            'index' => Pages\ListRents::route('/'),
            'create' => Pages\CreateRent::route('/create'),
            'edit' => Pages\EditRent::route('/{record}/edit'),
            'view' => Pages\ViewRent::route('/{record}'),
        ];
    }
    
    // ESQUEMAS MODALES PARA CREACIÓN RÁPIDA DE CLIENTES 

    /**
     * Esquema simplificado para la creación de un nuevo Inquilino
     */
    protected static function getTenantCreationForm(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Datos Básicos del Inquilino')
                ->schema([
                    Forms\Components\Radio::make('tipo_persona')
                        ->label('Tipo de Persona')
                        ->options([
                            'fisica' => 'Persona física',
                            'moral' => 'Persona moral',
                        ])
                        ->required()
                        ->default('fisica')
                        ->live(),
                    
                    Forms\Components\TextInput::make('nombres')
                        ->label('Nombre(s)')
                        ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                        ->maxLength(255)
                        ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica'),
                    
                    Forms\Components\TextInput::make('primer_apellido')
                        ->label('Primer Apellido')
                        ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                        ->maxLength(255)
                        ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica'),
                        
                    Forms\Components\TextInput::make('razon_social')
                        ->label('Razón Social')
                        ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                        ->maxLength(255)
                        ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'moral'),
                        
                    Forms\Components\TextInput::make('email')
                        ->label('Correo Electrónico')
                        ->email()
                        ->required()
                        ->maxLength(255),
                    
                    Forms\Components\TextInput::make('telefono_celular')
                        ->label('Teléfono Celular')
                        ->tel()
                        ->required()
                        ->maxLength(20),
                ])
                ->columns(2),
        ]);
    }

    /**
     * Esquema simplificado para la creación de un nuevo Propietario
     */
    protected static function getOwnerCreationForm(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Datos Básicos del Propietario')
                ->schema([
                    Forms\Components\Radio::make('tipo_persona')
                        ->label('Tipo de Persona')
                        ->options([
                            'fisica' => 'Persona física',
                            'moral' => 'Persona moral',
                        ])
                        ->required()
                        ->default('fisica')
                        ->live(),
                    
                    Forms\Components\TextInput::make('nombres')
                        ->label('Nombre(s)')
                        ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                        ->maxLength(255)
                        ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica'),
                    
                    Forms\Components\TextInput::make('primer_apellido')
                        ->label('Primer Apellido')
                        ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                        ->maxLength(255)
                        ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica'),
                        
                    Forms\Components\TextInput::make('razon_social')
                        ->label('Razón Social')
                        ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                        ->maxLength(255)
                        ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'moral'),
                        
                    Forms\Components\TextInput::make('email')
                        ->label('Correo Electrónico')
                        ->email()
                        ->required()
                        ->maxLength(255),
                    
                    Forms\Components\TextInput::make('telefono')
                        ->label('Teléfono')
                        ->tel()
                        ->required()
                        ->maxLength(20),
                ])
                ->columns(2),
        ]);
    }
}