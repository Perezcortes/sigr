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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

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
        // Admin, Gerente y Asesor pueden ver el menú de Usuarios
        return auth()->user()->hasAnyRole(['Administrador', 'Gerente', 'Asesor']);
    }

    public static function canCreate(): bool
    {
        // Solo el Administrador puede crear usuarios nuevos
        return auth()->user()->hasRole('Administrador');
    }

    public static function canEdit(Model $record): bool
    {
        // Solo el Administrador puede editar a los usuarios
        return auth()->user()->hasRole('Administrador');
    }

    public static function canDelete(Model $record): bool
    {
        // Solo el Administrador puede eliminar
        return auth()->user()->hasRole('Administrador');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // --- SECCIÓN 1: DATOS PERSONALES ---
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
                            ->revealable()
                            ->dehydrateStateUsing(fn ($state) => \Illuminate\Support\Facades\Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->maxLength(255),
                            
                        Forms\Components\TextInput::make('password_confirmation')
                            ->label('CONFIRMAR CONTRASEÑA')
                            ->password()
                            ->revealable()
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
                                    
                                // Banderas adicionales ocultas o visibles según se necesite
                                Forms\Components\Toggle::make('is_owner')->label('Es Propietario'),
                                Forms\Components\Toggle::make('is_tenant')->label('Es Inquilino'),
                            ]),

                        Forms\Components\Select::make('office_id')
                            ->label('Oficina Asignada')
                            ->relationship('office', 'nombre') 
                            ->searchable()
                            ->preload()
                            // Solo mostramos esto si el usuario actual es Admin
                            ->visible(fn () => auth()->user()->hasRole('Administrador')),

                        // Imagen de Perfil
                        \Filament\Forms\Components\SpatieMediaLibraryFileUpload::make('avatar')
                            ->label('IMAGEN DE PERFIL')
                            ->collection('profile-images') 
                            ->avatar() // Formato circular
                            ->alignCenter()
                            ->columnSpanFull(),
                    ])
                    ->columns(2), 

                // --- SECCIÓN 2: PERMISOS DIRECTOS DINÁMICOS ---
                Forms\Components\Section::make('Permisos Directos del Usuario')
                    ->description('Asigna permisos adicionales. Los permisos del Rol seleccionado arriba ya están incluidos.')
                    ->schema([
                        Forms\Components\Select::make('permissions')
                            ->label('PERMISOS EXTRA')
                            ->multiple()
                            ->relationship('permissions', 'name')
                            ->preload()
                            ->searchable()
                            // 1. Formulario en modal para crear nuevos permisos al vuelo
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nombre del nuevo permiso')
                                    ->placeholder('Ej: Exportar reportes Excel')
                                    ->required()
                                    ->unique(table: 'permissions', column: 'name')
                            ])
                            // 2. Lógica para guardarlo en la base de datos de Spatie
                            ->createOptionUsing(function (array $data) {
                                $permiso = \Spatie\Permission\Models\Permission::create([
                                    'name' => $data['name'],
                                    'guard_name' => 'web',
                                ]);
                                return $permiso->id;
                            })
                            // 3. Candado: Solo el Administrador ve el botón "+"
                            ->createOptionAction(fn (\Filament\Forms\Components\Actions\Action $action) => 
                                $action->visible(fn () => auth()->user()->hasRole('Administrador'))
                            )
                            ->columnSpanFull(),
                    ])
                    ->collapsible() 
                    ->collapsed(false),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        // El Administrador ve absolutamente todo
        if ($user->hasRole('Administrador')) {
            return $query;
        }

        // Gerentes y Asesores solo ven los usuarios que pertenecen a su misma oficina
        if ($user->hasAnyRole(['Gerente', 'Asesor'])) {
            return $query->where('office_id', $user->office_id);
        }

        return $query;
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
                        'Gerente' => 'info', 
                        'Asesor' => 'warning',
                        'Usuario', 'Cliente' => 'success',
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
                Tables\Actions\ViewAction::make()
                    ->iconButton()
                    ->tooltip('Ver detalles'),
                    
                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->tooltip('Editar'),
                    
                Tables\Actions\DeleteAction::make()
                    ->iconButton()
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