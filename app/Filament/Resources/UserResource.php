<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users'; 
    protected static ?string $navigationLabel = 'Usuarios';
    protected static ?string $modelLabel = 'Usuario';
    protected static ?string $navigationGroup = 'Administración';
    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        // Solo puede ver el menú si es Administrador
        return auth()->user()->hasRole('Administrador');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Usuario')
                    ->schema([
                        // Nombre y Correo
                        Forms\Components\TextInput::make('name')
                            ->label('NOMBRE')
                            ->required()
                            ->maxLength(255),
                            
                        Forms\Components\TextInput::make('email')
                            ->label('CORREO ELECTRÓNICO')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        // Móvil y Rol
                        Forms\Components\TextInput::make('mobile')
                            ->label('TELÉFONO MÓVIL')
                            ->tel()
                            ->maxLength(20),

                        // CAMPO ROL (Con Spatie)
                        Forms\Components\Select::make('roles')
                            ->label('ROL')
                            ->relationship('roles', 'name') // Relación directa con Spatie
                            ->multiple() // Permite múltiples roles si es necesario
                            ->preload()
                            ->searchable()
                            ->required(),

                        // Contraseña
                        Forms\Components\TextInput::make('password')
                            ->label('CONTRASEÑA')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->maxLength(255),
                            
                        Forms\Components\TextInput::make('password_confirmation')
                            ->label('CONFIRMAR CONTRASEÑA')
                            ->password()
                            ->required(fn (string $context): bool => $context === 'create')
                            ->same('password')
                            ->dehydrated(false), // No se guarda en la BD

                        // Activo y Banderas Extra
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('is_active')
                                    ->label('ACTIVO')
                                    ->onColor('success')
                                    ->offColor('danger')
                                    ->default(true),
                                    
                                // Banderas adicionales ocultas o visibles según necesites
                                Forms\Components\Toggle::make('is_owner')->label('Es Propietario'),
                                Forms\Components\Toggle::make('is_tenant')->label('Es Inquilino'),
                            ]),

                        Forms\Components\Select::make('office_id')
                            ->label('Oficina Asignada')
                            ->relationship('office', 'nombre') // Asegúrate que tu modelo Office tenga columna 'nombre' o 'name'
                            ->searchable()
                            ->preload()
                            // Solo mostramos esto si el usuario actual es Admin, 
                            // o dejamos que se vea siempre si esa es la regla.
                            ->visible(fn () => auth()->user()->hasRole('Administrador')),

                        // Imagen de Perfil
                        SpatieMediaLibraryFileUpload::make('avatar')
                            ->label('IMAGEN DE PERFIL')
                            ->collection('profile-images') // Colección definida en tu modelo
                            ->avatar() // Formato circular
                            ->alignCenter()
                            ->columnSpanFull(),
                    ])
                    ->columns(2) // Estructura de 2 columnas como en tu imagen
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\SpatieMediaLibraryImageColumn::make('avatar')
                    ->label('Avatar')
                    ->collection('profile-images')
                    ->circular(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Correo')
                    ->searchable(),
                
                // Mostrar Roles (Badge de colores)
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Rol')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Administrador' => 'danger',
                        'Asesor' => 'warning',
                        'Usuario' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha Creación')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Filtro por Rol
                Tables\Filters\SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->label('Filtrar por Rol'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->iconButton() // Convierte el botón a solo icono
                    ->tooltip('Editar'),
                Tables\Actions\DeleteAction::make()
                    ->iconButton() // Convierte el botón a solo icono
                    ->tooltip('Eliminar'),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}