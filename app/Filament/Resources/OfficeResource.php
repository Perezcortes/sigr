<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OfficeResource\Pages;
use App\Models\Office;
use App\Models\City; 
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Get; 
use Filament\Forms\Set; 
use Illuminate\Support\Collection; 

class OfficeResource extends Resource
{
    protected static ?string $model = Office::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationGroup = 'Administración';

    protected static ?string $navigationLabel = 'Oficinas';

    protected static ?string $modelLabel = 'Oficina';

    protected static ?string $pluralModelLabel = 'Oficinas';

    public static function getCluster(): ?string
    {
        return null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información Básica')
                    ->schema([
                        Forms\Components\TextInput::make('nombre')
                            ->required()
                            ->maxLength(255)
                            ->label('Nombre'),
                        Forms\Components\TextInput::make('telefono')
                            ->tel()
                            ->maxLength(20)
                            ->label('Teléfono'),
                        Forms\Components\TextInput::make('correo')
                            ->email()
                            ->unique(ignoreRecord: true)
                            ->maxLength(150)
                            ->label('Correo'),
                        Forms\Components\TextInput::make('responsable')
                            ->maxLength(100)
                            ->label('Responsable'),
                        Forms\Components\TextInput::make('clave')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->label('Clave'),
                    ])->columns(2),

                Forms\Components\Section::make('Estatus')
                    ->schema([
                        Forms\Components\Toggle::make('estatus_actividad')
                            ->label('Activo')
                            ->default(true),
                        Forms\Components\Toggle::make('estatus_recibir_leads')
                            ->label('Recibir Leads')
                            ->default(false),
                    ])->columns(2),

                Forms\Components\Section::make('Dirección')
                    ->schema([
                        Forms\Components\TextInput::make('calle')
                            ->required()
                            ->maxLength(100)
                            ->label('Calle'),
                        Forms\Components\TextInput::make('numero_interior')
                            ->maxLength(20)
                            ->label('Número Interior'),
                        Forms\Components\TextInput::make('numero_exterior')
                            ->required()
                            ->maxLength(20)
                            ->label('Número Exterior'),
                        Forms\Components\TextInput::make('colonia')
                            ->required()
                            ->maxLength(100)
                            ->label('Colonia'),
                        Forms\Components\TextInput::make('delegacion_municipio')
                            ->required()
                            ->maxLength(100)
                            ->label('Delegación/Municipio'),
                        Forms\Components\TextInput::make('codigo_postal')
                            ->maxLength(10)
                            ->label('Código Postal'),

                        // === SELECTS DEPENDIENTES (ESTADO -> CIUDAD) ===
                        
                        Forms\Components\Select::make('estate_id')
                            ->relationship('estate', 'nombre')
                            ->label('Estado')
                            ->searchable()
                            ->preload()
                            ->live() // Hace reactivo el campo
                            ->required(),

                        Forms\Components\TextInput::make('ciudad')
                            ->label('Ciudad')
                            ->required()
                            ->maxLength(100),
                        // ===============================================

                    ])->columns(2),

                Forms\Components\Section::make('Geolocalización')
                    ->schema([
                        Forms\Components\TextInput::make('lat')
                            ->numeric()
                            ->step(0.0000001)
                            ->label('Latitud'),
                        Forms\Components\TextInput::make('lng')
                            ->numeric()
                            ->step(0.0000001)
                            ->label('Longitud'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->searchable()
                    ->sortable()
                    ->label('Nombre'),
                Tables\Columns\TextColumn::make('telefono')
                    ->searchable()
                    ->label('Teléfono'),
                Tables\Columns\TextColumn::make('correo')
                    ->searchable()
                    ->label('Correo'),
                Tables\Columns\TextColumn::make('responsable')
                    ->searchable()
                    ->label('Responsable'),
                Tables\Columns\TextColumn::make('clave')
                    ->searchable()
                    ->label('Clave'),
                Tables\Columns\IconColumn::make('estatus_actividad')
                    ->boolean()
                    ->label('Activo'),
                Tables\Columns\IconColumn::make('estatus_recibir_leads')
                    ->boolean()
                    ->label('Recibir Leads'),
                
                // Columnas de relación corregidas
                Tables\Columns\TextColumn::make('ciudad')
                    ->label('Ciudad')
                    ->searchable(),
                Tables\Columns\TextColumn::make('estate.nombre')
                    ->searchable()
                    ->sortable()
                    ->label('Estado'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('estatus_actividad')
                    ->options([
                        '1' => 'Activo',
                        '0' => 'Inactivo',
                    ])
                    ->label('Estatus Actividad'),
                Tables\Filters\SelectFilter::make('estatus_recibir_leads')
                    ->options([
                        '1' => 'Sí',
                        '0' => 'No',
                    ])
                    ->label('Recibir Leads'),
                Tables\Filters\SelectFilter::make('estate')
                    ->relationship('estate', 'nombre')
                    ->searchable()
                    ->preload()
                    ->label('Estado'),
                Tables\Filters\SelectFilter::make('city')
                    ->relationship('city', 'nombre')
                    ->searchable()
                    ->preload()
                    ->label('Ciudad'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
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
            'index' => Pages\ListOffices::route('/'),
            'create' => Pages\CreateOffice::route('/create'),
            'edit' => Pages\EditOffice::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->with(['city', 'estate']); // Eager loading para optimizar consultas
    }
}