<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RoleResource extends Resource
{
    // 1. Le decimos que use el modelo de Spatie
    protected static ?string $model = Role::class;

    // 2. Personalizamos el menú
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = 'Administración';
    protected static ?string $navigationLabel = 'Roles y Permisos';
    protected static ?string $modelLabel = 'Rol';
    protected static ?string $pluralModelLabel = 'Roles';
    protected static ?int $navigationSort = 1; 

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detalles del Rol')
                    ->description('Crea un nuevo rol y asígnale los permisos base.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre del Rol')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->placeholder('Ej: Asistente, Auditor, etc.')
                            ->maxLength(255),

                        Forms\Components\Select::make('permissions')
                            ->label('Permisos Asignados')
                            ->multiple()
                            ->relationship('permissions', 'name')
                            ->preload()
                            ->searchable()
                            // Formulrio modal para crear un permiso nuevo si no existe
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nombre del nuevo permiso')
                                    ->required()
                                    ->unique(table: 'permissions', column: 'name')
                            ])
                            ->createOptionUsing(function (array $data) {
                                $permiso = Permission::create([
                                    'name' => $data['name'],
                                    'guard_name' => 'web',
                                ]);
                                return $permiso->id;
                            })
                            ->columnSpanFull(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre del Rol')
                    ->searchable()
                    ->sortable(),

                // Mostramos los permisos como "etiquetas"
                Tables\Columns\TextColumn::make('permissions.name')
                    ->label('Permisos que incluye')
                    ->badge()
                    ->color('success')
                    ->separator(',')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
           ->actions([
                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->tooltip('Editar'),
                    
                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->tooltip('Eliminar')
                    // Protegemos el rol Administrador para que nadie lo borre por accidente
                    ->hidden(fn (Role $record) => $record->name === 'Administrador'), 
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}