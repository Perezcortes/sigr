<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TenantResource\Pages;
use App\Helpers\EstadosMexico;
use App\Models\Tenant;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Actions;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Mail;
use App\Mail\TenantCredentialsMail;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Inquilinos';

    protected static ?string $modelLabel = 'Inquilino';

    protected static ?string $pluralModelLabel = 'Inquilinos';

    protected static ?string $navigationGroup = 'Rentas';

    protected static ?int $navigationSort = 1;

    public static function getCluster(): ?string
    {
        return null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Tipo de Persona')
                    ->schema([
                        Forms\Components\Radio::make('tipo_persona')
                            ->options([
                                'fisica' => 'Persona Física',
                                'moral' => 'Persona Moral',
                            ])
                            ->required()
                            ->live()
                            ->columnSpanFull(),
                    ]),

                // Formulario Persona Física
                Forms\Components\Section::make('Información Personal')
                    ->schema(self::getPersonaFisicaSchema())
                    ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                    ->columns(2),

                Forms\Components\Section::make('Datos del Cónyuge')
                    ->schema(self::getConyugeSchema())
                    ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica' && $get('estado_civil') === 'casado')
                    ->columns(2),

                // Formulario Persona Moral
                Forms\Components\Section::make('Datos de la Empresa')
                    ->schema(self::getPersonaMoralSchema())
                    ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                    ->columns(2),

                Forms\Components\Section::make('Datos del Acta Constitutiva')
                    ->schema(self::getActaConstitutivaSchema())
                    ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                    ->columns(2),

                Forms\Components\Section::make('Apoderado Legal y/o Representante')
                    ->schema(self::getApoderadoSchema())
                    ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                    ->columns(2),

                Forms\Components\Section::make('Facultades en Acta')
                    ->schema(self::getFacultadesActaSchema())
                    ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'moral' && $get('facultades_en_acta') === true)
                    ->columns(2),
                
                Forms\Components\Section::make('Credenciales de Acceso y Envío')
                    ->description('Zona exclusiva para Administradores y Gerentes. Genera accesos y notifica al cliente.')
                    ->icon('heroicon-o-lock-closed')
                    ->schema(self::getCredencialesSchema()) // Llamamos al método que creamos arriba
                    ->columns(2)
                    ->visible(fn ($record) => $record !== null) 
                    ->hidden(fn () => !auth()->user()->hasRole(['Administrador', 'Gerente', 'Asesor'])), 
            ]);
    }

    protected static function getPersonaFisicaSchema(): array
    {
        return [
            Forms\Components\TextInput::make('nombres')
                ->label('Nombre(s)')
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                ->maxLength(255),

            Forms\Components\TextInput::make('primer_apellido')
                ->label('Apellido Paterno')
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                ->maxLength(255),

            Forms\Components\TextInput::make('segundo_apellido')
                ->label('Apellido Materno')
                ->maxLength(255),

            Forms\Components\TextInput::make('email')
                ->label('E-mail')
                ->email()
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                ->unique(ignoreRecord: true)
                ->maxLength(255),

            Forms\Components\TextInput::make('email_confirmacion')
                ->label('Confirmar E-mail')
                ->email()
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                ->same('email')
                ->maxLength(255),

            Forms\Components\Radio::make('nacionalidad')
                ->label('Nacionalidad')
                ->options([
                    'mexicana' => 'Mexicana',
                    'otra' => 'Otra',
                ])
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                ->live(),

            Forms\Components\TextInput::make('nacionalidad_especifica')
                ->label('Especifique')
                ->required(fn (Forms\Get $get) => $get('nacionalidad') === 'otra')
                ->visible(fn (Forms\Get $get) => $get('nacionalidad') === 'otra')
                ->maxLength(255),

            Forms\Components\Radio::make('sexo')
                ->label('Sexo')
                ->options([
                    'masculino' => 'Masculino',
                    'femenino' => 'Femenino',
                ])
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica'),

            Forms\Components\Radio::make('estado_civil')
                ->label('Estado Civil')
                ->options([
                    'soltero' => 'Soltero',
                    'casado' => 'Casado',
                ])
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                ->live(),

            Forms\Components\Select::make('tipo_identificacion')
                ->label('Identificación')
                ->options([
                    'INE' => 'INE',
                    'Pasaporte' => 'Pasaporte',
                    'Cedula' => 'Cédula',
                    'Licencia' => 'Licencia',
                    'Otro' => 'Otro',
                ])
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica'),

            Forms\Components\DatePicker::make('fecha_nacimiento')
                ->label('Fecha de Nacimiento')
                ->displayFormat('d/m/Y')
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                ->native(false),

            Forms\Components\TextInput::make('rfc')
                ->label('RFC')
                ->maxLength(13)
                ->rules(['regex:/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/i']),

            Forms\Components\TextInput::make('curp')
                ->label('CURP')
                ->maxLength(18)
                ->rules(['regex:/^[A-Z]{4}\d{6}[HM][A-Z]{5}[0-9A-Z]\d$/i']),

            Forms\Components\TextInput::make('telefono_celular')
                ->label('Teléfono Celular')
                ->tel()
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                ->maxLength(20),

            Forms\Components\TextInput::make('telefono_fijo')
                ->label('Teléfono Fijo')
                ->tel()
                ->maxLength(20),
        ];
    }

    protected static function getConyugeSchema(): array
    {
        return [
            Forms\Components\TextInput::make('conyuge_nombres')
                ->label('Nombre(s)')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('conyuge_primer_apellido')
                ->label('Apellido Paterno')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('conyuge_segundo_apellido')
                ->label('Apellido Materno')
                ->maxLength(255),

            Forms\Components\TextInput::make('conyuge_telefono')
                ->label('Teléfono')
                ->tel()
                ->maxLength(20),
        ];
    }

    protected static function getPersonaMoralSchema(): array
    {
        return [
            Forms\Components\TextInput::make('razon_social')
                ->label('Razón Social')
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                ->maxLength(255),

            Forms\Components\TextInput::make('email')
                ->label('Correo Electrónico')
                ->email()
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                ->unique(ignoreRecord: true)
                ->maxLength(255),

            Forms\Components\TextInput::make('dominio_internet')
                ->label('Dominio de Internet')
                ->maxLength(255),

            Forms\Components\TextInput::make('rfc')
                ->label('RFC')
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                ->maxLength(13)
                ->rules(['regex:/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/i']),

            Forms\Components\TextInput::make('telefono')
                ->label('Teléfono')
                ->tel()
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                ->maxLength(20),

            Forms\Components\TextInput::make('calle')
                ->label('Calle')
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                ->maxLength(255),

            Forms\Components\TextInput::make('numero_exterior')
                ->label('Número Exterior')
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                ->maxLength(20),

            Forms\Components\TextInput::make('numero_interior')
                ->label('Número Interior')
                ->maxLength(20),

            Forms\Components\TextInput::make('cp')
                ->label('Código Postal')
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                ->maxLength(5)
                ->rules(['regex:/^\d{5}$/']),

            Forms\Components\TextInput::make('colonia')
                ->label('Colonia')
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                ->maxLength(255),

            Forms\Components\TextInput::make('municipio')
                ->label('Municipio')
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                ->maxLength(255),

            Forms\Components\Select::make('estado')
                ->label('Estado')
                ->options(EstadosMexico::getEstados())
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                ->searchable(),

            Forms\Components\TextInput::make('ingreso_mensual_promedio')
                ->label('Ingreso Mensual Promedio')
                ->numeric()
                ->prefix('$')
                ->maxValue(999999999.99),

            Forms\Components\Textarea::make('referencias_ubicacion')
                ->label('Referencias de Ubicación')
                ->rows(3)
                ->maxLength(500),
        ];
    }

    protected static function getActaConstitutivaSchema(): array
    {
        return [
            Forms\Components\TextInput::make('notario_nombres')
                ->label('Nombre(s) del Notario')
                ->maxLength(255),

            Forms\Components\TextInput::make('notario_primer_apellido')
                ->label('Apellido Paterno')
                ->maxLength(255),

            Forms\Components\TextInput::make('notario_segundo_apellido')
                ->label('Apellido Materno')
                ->maxLength(255),

            Forms\Components\TextInput::make('numero_escritura')
                ->label('No. de Escritura')
                ->maxLength(50),

            Forms\Components\DatePicker::make('fecha_constitucion')
                ->label('Fecha de Constitución')
                ->displayFormat('d/m/Y')
                ->native(false),

            Forms\Components\TextInput::make('notario_numero')
                ->label('Notario Número')
                ->maxLength(50),

            Forms\Components\TextInput::make('ciudad_registro')
                ->label('Ciudad de Registro')
                ->maxLength(255),

            Forms\Components\Select::make('estado_registro')
                ->label('Estado de Registro')
                ->options(EstadosMexico::getEstados())
                ->searchable(),

            Forms\Components\TextInput::make('numero_registro_inscripcion')
                ->label('Número de Registro o Inscripción')
                ->maxLength(100),

            Forms\Components\TextInput::make('giro_comercial')
                ->label('Giro Comercial')
                ->maxLength(255),
        ];
    }

    protected static function getApoderadoSchema(): array
    {
        return [
            Forms\Components\TextInput::make('apoderado_nombres')
                ->label('Nombre(s)')
                ->maxLength(255),

            Forms\Components\TextInput::make('apoderado_primer_apellido')
                ->label('Apellido Paterno')
                ->maxLength(255),

            Forms\Components\TextInput::make('apoderado_segundo_apellido')
                ->label('Apellido Materno')
                ->maxLength(255),

            Forms\Components\Select::make('apoderado_sexo')
                ->label('Sexo')
                ->options([
                    'masculino' => 'Masculino',
                    'femenino' => 'Femenino',
                ]),

            Forms\Components\TextInput::make('apoderado_telefono')
                ->label('Teléfono')
                ->tel()
                ->maxLength(20),

            Forms\Components\TextInput::make('apoderado_extension')
                ->label('Extensión')
                ->maxLength(10),

            Forms\Components\TextInput::make('apoderado_email')
                ->label('Correo Electrónico')
                ->email()
                ->maxLength(255),

            Forms\Components\Radio::make('facultades_en_acta')
                ->label('¿Sus facultades constan en el acta constitutiva?')
                ->options([
                    false => 'No',
                    true => 'Sí',
                ])
                ->required()
                ->live()
                ->columnSpanFull(),
        ];
    }

    protected static function getFacultadesActaSchema(): array
    {
        return [
            Forms\Components\TextInput::make('escritura_publica_numero')
                ->label('Escritura Pública o Acta Número')
                ->required()
                ->maxLength(50),

            Forms\Components\TextInput::make('notario_numero_facultades')
                ->label('Notario Número')
                ->required()
                ->maxLength(50),

            Forms\Components\DatePicker::make('fecha_escritura_facultades')
                ->label('Fecha de Escritura o Acta')
                ->displayFormat('d/m/Y')
                ->required()
                ->native(false),

            Forms\Components\TextInput::make('numero_inscripcion_registro_publico')
                ->label('No. de Inscripción en el Registro Público')
                ->required()
                ->maxLength(100),

            Forms\Components\TextInput::make('ciudad_registro_facultades')
                ->label('Ciudad de Registro')
                ->required()
                ->maxLength(255),

            Forms\Components\Select::make('estado_registro_facultades')
                ->label('Estado de Registro')
                ->options(EstadosMexico::getEstados())
                ->required()
                ->searchable(),

            Forms\Components\DatePicker::make('fecha_inscripcion_facultades')
                ->label('Fecha de Inscripción')
                ->displayFormat('d/m/Y')
                ->required()
                ->native(false),

            Forms\Components\Select::make('tipo_representacion')
                ->label('Tipo de Representación')
                ->options([
                    'Administrador único' => 'Administrador único',
                    'Presidente del consejo' => 'Presidente del consejo',
                    'Socio administrador' => 'Socio administrador',
                    'Gerente' => 'Gerente',
                    'Otro' => 'Otro',
                ])
                ->required(),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        // Si es Admin, ve todo.
        if ($user->hasRole('Administrador')) {
            return $query;
        }

        // Si es Asesor, solo ve los registros donde él es el 'asesor_id'
        if ($user->hasRole('Asesor')) {
            return $query->where('asesor_id', $user->id);
        }

        // Por defecto, restringir o mostrar todo según el caso
        return $query;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre_completo')
                    ->label('Nombre Completo')
                    ->searchable(['nombres', 'primer_apellido', 'segundo_apellido', 'razon_social'])
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Correo')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('telefono_celular')
                    ->label('Teléfono')
                    ->searchable()
                    ->default('N/A')
                    ->visible(fn ($record) => $record?->tipo_persona === 'fisica'),

                Tables\Columns\TextColumn::make('telefono')
                    ->label('Teléfono')
                    ->searchable()
                    ->default('N/A')
                    ->visible(fn ($record) => $record?->tipo_persona === 'moral'),

                Tables\Columns\TextColumn::make('tipo_persona')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'fisica' => 'Persona Física',
                        'moral' => 'Persona Moral',
                        default => 'N/A',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'fisica' => 'success',
                        'moral' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('estado_civil')
                    ->label('Estado Civil')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'soltero' => 'Soltero',
                        'casado' => 'Casado',
                        default => 'N/A',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de Creación')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tipo_persona')
                    ->label('Tipo de Persona')
                    ->options([
                        'fisica' => 'Persona Física',
                        'moral' => 'Persona Moral',
                    ]),

                Tables\Filters\SelectFilter::make('estado_civil')
                    ->label('Estado Civil')
                    ->options([
                        'soltero' => 'Soltero',
                        'casado' => 'Casado',
                    ]),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenants::route('/'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }

    public static function getCredencialesSchema(): array
    {
        return [
            Forms\Components\TextInput::make('login_email')
                ->label('Correo de Acceso (Usuario)')
                ->email()
                ->required()
                ->default(fn ($record) => $record->email) 
                ->dehydrated(false) // No guardar en tabla tenants
                ->formatStateUsing(fn ($record) => $record->email),

            Forms\Components\TextInput::make('login_password')
                ->label('Contraseña')
                ->password()
                ->revealable()
                // Generamos una contraseña aleatoria sugerida
                ->default(fn () => \Illuminate\Support\Str::random(10)) 
                ->helperText('Esta contraseña se encriptará en la base de datos. El usuario la recibirá en su correo.')
                ->dehydrated(false), // No guardar en tabla tenants

            // BOTÓN DE ACCIÓN
            Forms\Components\Actions::make([
                Action::make('enviar_accesos')
                    ->label('Generar Usuario y Enviar Correo')
                    ->icon('heroicon-m-envelope')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Confirmar envío')
                    ->modalDescription('Se creará/actualizará el usuario de sistema y se enviarán estas credenciales por correo.')
                    ->action(function (Forms\Set $set, Forms\Get $get, $record) {
                        
                        // Obtenemos los datos del formulario virtual
                        $email = $get('login_email');
                        $password = $get('login_password');

                        if (!$email || !$password) {
                            Notification::make()->danger()->title('Error')->body('Correo y contraseña requeridos.')->send();
                            return;
                        }

                        // LÓGICA CRÍTICA: CREAR O ACTUALIZAR EL USUARIO EN LA TABLA USERS
                        // Buscamos si ya existe un usuario con este correo para no duplicar
                        $user = User::updateOrCreate(
                            ['email' => $email], // Buscamos por correo
                            [
                                'name'      => $record->nombre_completo ?? $record->nombres, // Usamos el nombre del inquilino
                                'password'  => Hash::make($password), // ¡IMPORTANTE! Se guarda ENCRIPTADA
                                'is_tenant' => true,
                                'is_active' => true,
                                // 'office_id' => $record->asesor->office_id ?? null, // Opcional si hereda oficina
                            ]
                        );

                        // VINCULACIÓN: Guardamos el ID del usuario en el registro del Inquilino
                        // Si el inquilino no tenía user_id, ahora ya lo tiene.
                        if ($record->user_id !== $user->id) {
                            $record->update(['user_id' => $user->id]);
                        }

                        // D. ENVÍO DE CORREO
                        try {
                            Mail::to($user->email)->send(new TenantCredentialsMail($user, $password));
        
                            Notification::make()
                                ->success()
                                ->title('Éxito')
                                ->body("Credenciales enviadas a {$email}")
                                ->send();
            
                        } catch (\Exception $e) {
                            Notification::make()
                                ->warning()
                                ->title('Usuario guardado, pero falló el correo')
                                ->body('Error SMTP: ' . $e->getMessage())
                                ->send();
                        }

                        // Opcional: Limpiar campo
                        $set('login_password', \Illuminate\Support\Str::random(10));

                        // Notificación de éxito
                        Notification::make()
                            ->success()
                            ->title('Usuario Configurado')
                            ->body("Se asignó el usuario ID: {$user->id} y se enviaron las credenciales a {$email}.")
                            ->send();
                        
                        // Opcional: Limpiar el campo de contraseña para seguridad visual
                        $set('login_password', \Illuminate\Support\Str::random(10));
                    }),
            ])->columnSpanFull(),
        ];
    }
}
