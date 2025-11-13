<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RentResource\Pages;
use App\Models\Rent;
use App\Models\Tenant;
use App\Models\Owner;
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
    protected static ?string $navigationGroup = 'Clientes';
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
                        // Solo los campos esenciales
                        Select::make('tenant_id')
                            ->relationship('tenant', 'nombres')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->nombre_completo)
                            ->searchable(['nombres', 'primer_apellido', 'razon_social'])
                            ->preload()
                            ->required()
                            ->label('Seleccionar Inquilino')
                            ->createOptionForm(fn (Form $form) => self::getTenantCreationForm($form))
                            ->createOptionModalHeading('Crear Nuevo Inquilino'),
                        
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
            ->columns([
                TextColumn::make('tenant.nombres')
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

                TextColumn::make('owner.nombres')
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

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tenant_id')
                    ->relationship('tenant', 'nombres')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->nombre_completo)
                    ->searchable()
                    ->label('Filtrar por Inquilino'),

                Tables\Filters\SelectFilter::make('owner_id')
                    ->relationship('owner', 'nombres')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->nombre_completo)
                    ->searchable()
                    ->label('Filtrar por Propietario'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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