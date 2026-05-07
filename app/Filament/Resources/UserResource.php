<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

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
        return auth()->user()->hasAnyRole(['Administrador', 'Gerente', 'Agente', 'Asesor']);
    }

    public static function canCreate(): bool
    {
        // Administradores y usuarios con el permiso explícito pueden crear usuarios nuevos
        return auth()->user()->hasAnyRole(['Administrador', 'Gerente'])
            || auth()->user()->can('Gestionar Usuarios');
    }

    public static function canEdit(Model $record): bool
    {
        // Permitir a quien tenga permiso explícito (y al Administrador)
        return auth()->user()->hasRole('Administrador')
            || auth()->user()->can('Gestionar Usuarios');
    }

    public static function canDelete(Model $record): bool
    {
        // Permitir a quien tenga permiso explícito (y al Administrador)
        return auth()->user()->hasRole('Administrador')
            || auth()->user()->can('Gestionar Usuarios');
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

                        Forms\Components\Select::make('primary_role')
                            ->label('ROL')
                            ->options(fn () => Role::query()->orderBy('name')->pluck('name', 'name')->all())
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $creator = auth()->user();

                                if ($state !== 'Cliente') {
                                    $set('asesor_id', null);
                                    $set('is_owner', false);
                                    $set('is_tenant', false);
                                    $set('is_seller', false);
                                    $set('is_buyer', false);
                                }
                                if (! in_array((string) $state, ['Gerente', 'Agente', 'Asesor', 'Cliente'], true)) {
                                    $set('office_id', null);
                                }

                                // Si un Gerente crea un Agente/Asesor, forzar su misma oficina
                                if (
                                    $creator?->hasRole('Gerente')
                                    && in_array((string) $state, ['Agente', 'Asesor'], true)
                                    && ! empty($creator->office_id)
                                ) {
                                    $set('office_id', $creator->office_id);
                                }
                            }),

                        // Contraseña
                        Forms\Components\TextInput::make('password')
                            ->label('CONTRASEÑA')
                            ->password()
                            ->revealable()
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
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

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('is_active')
                                    ->label('ACTIVO')
                                    ->onColor('success')
                                    ->offColor('danger')
                                    ->default(true),
                            ]),

                        Forms\Components\Select::make('office_id')
                            ->label('Oficina asignada')
                            ->relationship('office', 'nombre')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->disabled(function (Get $get) {
                                $user = auth()->user();
                                $role = (string) $get('primary_role');

                                return $user?->hasRole('Gerente')
                                    && in_array($role, ['Agente', 'Asesor'], true)
                                    && ! empty($user->office_id);
                            })
                            ->visible(fn (Get $get): bool => in_array(
                                (string) $get('primary_role'),
                                ['Gerente', 'Agente', 'Asesor', 'Cliente'],
                                true
                            ))
                            ->required(fn (Get $get): bool => in_array(
                                (string) $get('primary_role'),
                                ['Gerente', 'Agente', 'Asesor', 'Cliente'],
                                true
                            ))
                            ->afterStateUpdated(fn (callable $set) => $set('asesor_id', null)),

                        Forms\Components\Select::make('evolution_whatsapp_instance_id')
                            ->label('Instancia WhatsApp (Evolution)')
                            ->relationship(
                                'evolutionWhatsappInstance',
                                'name',
                                fn ($query) => $query->orderBy('name')
                            )
                            ->searchable()
                            ->preload()
                            ->helperText('Opcional. Se usa al enviar mensajes desde Interesados con la cuenta de este usuario.')
                            ->visible(fn (Get $get): bool => in_array(
                                (string) $get('primary_role'),
                                ['Gerente', 'Agente', 'Asesor'],
                                true
                            )),

                        Forms\Components\Select::make('asesor_id')
                            ->label('Asesor de la oficina')
                            ->options(function (Get $get): array {
                                $officeId = $get('office_id');
                                if (! $officeId) {
                                    return [];
                                }

                                return User::query()
                                    ->where('office_id', $officeId)
                                    ->whereHas('roles', fn (Builder $q) => $q->whereIn('name', ['Agente', 'Asesor']))
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all();
                            })
                            ->searchable()
                            ->visible(fn (Get $get): bool => $get('primary_role') === 'Cliente')
                            ->required(fn (Get $get): bool => $get('primary_role') === 'Cliente')
                            ->rule(function (Get $get) {
                                return function (string $attribute, mixed $value, \Closure $fail) use ($get): void {
                                    if ($get('primary_role') !== 'Cliente' || $value === null || $value === '') {
                                        return;
                                    }
                                    $asesor = User::query()->find((int) $value);
                                    if (! $asesor || (int) $asesor->office_id !== (int) $get('office_id')) {
                                        $fail('El asesor debe pertenecer a la oficina seleccionada.');
                                    }
                                };
                            }),

                        Forms\Components\Grid::make(2)
                            ->visible(fn (Get $get): bool => $get('primary_role') === 'Cliente')
                            ->schema([
                                Forms\Components\Toggle::make('is_owner')
                                    ->label('Es propietario')
                                    ->default(false),
                                Forms\Components\Toggle::make('is_tenant')
                                    ->label('Es inquilino')
                                    ->default(false),
                                Forms\Components\Toggle::make('is_seller')
                                    ->label('Es vendedor')
                                    ->default(false),
                                Forms\Components\Toggle::make('is_buyer')
                                    ->label('Es comprador')
                                    ->default(false),
                            ]),

                        // Imagen de Perfil
                        SpatieMediaLibraryFileUpload::make('avatar')
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
                                    ->unique(table: 'permissions', column: 'name'),
                            ])
                            // 2. Lógica para guardarlo en la base de datos de Spatie
                            ->createOptionUsing(function (array $data) {
                                $permiso = Permission::create([
                                    'name' => $data['name'],
                                    'guard_name' => 'web',
                                ]);

                                return $permiso->id;
                            })
                            // 3. Candado: Solo el Administrador ve el botón "+"
                            ->createOptionAction(fn (Action $action) => $action->visible(fn () => auth()->user()->hasRole('Administrador'))
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
        if ($user->hasAnyRole(['Gerente', 'Agente', 'Asesor'])) {
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
