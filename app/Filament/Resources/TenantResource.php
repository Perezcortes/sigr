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
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\TenantCredentialsMail;
use Illuminate\Support\HtmlString;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Inquilinos';
    protected static ?string $modelLabel = 'Inquilino';
    protected static ?string $pluralModelLabel = 'Inquilinos';
    protected static ?string $navigationGroup = 'Rentas';
    protected static ?int $navigationSort = 2;

    public static function getCluster(): ?string
    {
        return null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(4)->schema([
                    
                    // COLUMNA IZQUIERDA (Perfil Rápido)
                    Forms\Components\Group::make()->columnSpan(1)->schema([
                        Forms\Components\Section::make('Perfil del Inquilino')->schema([
                            
                            Forms\Components\Radio::make('tipo_persona')
                                ->options([
                                    'fisica' => 'Persona Física',
                                    'moral' => 'Persona Moral',
                                ])
                                ->required()
                                ->live(),

                            // Si es persona Física
                            Forms\Components\TextInput::make('nombres')
                                ->label('Nombre(s)')
                                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                                ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                                ->maxLength(255),
                                
                            Forms\Components\TextInput::make('primer_apellido')
                                ->label('Apellido Paterno')
                                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                                ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                                ->maxLength(255),
                                
                            Forms\Components\TextInput::make('segundo_apellido')
                                ->label('Apellido Materno')
                                ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                                ->maxLength(255),

                            // Si es persona Moral
                            Forms\Components\TextInput::make('razon_social')
                                ->label('Razón Social')
                                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                                ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                                ->maxLength(255),

                            // Contacto Junto
                            Forms\Components\TextInput::make('email')
                                ->label('Correo Electrónico')
                                ->email()
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->prefixIcon('heroicon-m-envelope')
                                ->maxLength(255),

                            Forms\Components\TextInput::make('telefono_celular')
                                ->label('Teléfono Celular')
                                ->tel()
                                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                                ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                                ->prefixIcon('heroicon-m-phone')
                                ->maxLength(20),

                            Forms\Components\TextInput::make('telefono')
                                ->label('Teléfono (Moral)')
                                ->tel()
                                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                                ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                                ->prefixIcon('heroicon-m-phone')
                                ->maxLength(20),

                            Forms\Components\Select::make('asesor_id')
                                ->relationship('asesor', 'name')
                                ->label('Asesor Asignado'),
                        ]),
                    ]),

                    // COLUMNA DERECHA (Pestañas)
                    Forms\Components\Group::make()->columnSpan(3)->schema([
                        Forms\Components\Tabs::make('CRM Tabs')->tabs([
                            
                            // --- NOTAS Y ACCIONES ---
                            Forms\Components\Tabs\Tab::make('CRM y Seguimiento')
                                ->icon('heroicon-m-document-text')
                                ->schema([
                                    Forms\Components\Actions::make([
                                        Forms\Components\Actions\Action::make('agregar_nota')
                                            ->label('Agregar Nota')
                                            ->icon('heroicon-m-pencil-square')
                                            ->color('warning')
                                            ->form([
                                                Forms\Components\Textarea::make('nota')->required()->rows(3),
                                            ])
                                            ->action(function (array $data, ?Tenant $record) {
                                                if($record) {
                                                    $hist = $record->historial_acciones ?? [];
                                                    $hist[] = ['fecha' => now()->format('d/m/Y H:i'), 'accion' => 'Nota: ' . $data['nota']];
                                                    $record->update(['historial_acciones' => $hist]);
                                                }
                                            })->visible(fn (?Tenant $record) => $record !== null),

                                        Forms\Components\Actions\Action::make('whatsapp')
                                            ->label('WhatsApp')
                                            ->icon('heroicon-m-chat-bubble-left-ellipsis')
                                            ->color('success')
                                            ->action(function (?Tenant $record) {
                                                if($record) {
                                                    $hist = $record->historial_acciones ?? [];
                                                    $hist[] = ['fecha' => now()->format('d/m/Y H:i'), 'accion' => 'WhatsApp iniciado'];
                                                    $record->update(['historial_acciones' => $hist]);
                                                    $telefono = $record->tipo_persona === 'fisica' ? $record->telefono_celular : $record->telefono;
                                                    return redirect()->away("https://wa.me/52" . $telefono);
                                                }
                                            })->visible(fn (?Tenant $record) => $record !== null),

                                        Forms\Components\Actions\Action::make('registrar_llamada')
                                            ->label('Llamada')
                                            ->icon('heroicon-m-phone')
                                            ->color('gray')
                                            ->requiresConfirmation()
                                            ->action(function (?Tenant $record) {
                                                if($record) {
                                                    $hist = $record->historial_acciones ?? [];
                                                    $hist[] = ['fecha' => now()->format('d/m/Y H:i'), 'accion' => 'Llamada telefónica realizada'];
                                                    $record->update(['historial_acciones' => $hist]);
                                                }
                                            })->visible(fn (?Tenant $record) => $record !== null),
                                            
                                        Forms\Components\Actions\Action::make('enviar_email')
                                            ->label('Email')
                                            ->icon('heroicon-m-envelope')
                                            ->color('gray')
                                            ->requiresConfirmation()
                                            ->action(function (?Tenant $record) {
                                                if($record) {
                                                    $hist = $record->historial_acciones ?? [];
                                                    $hist[] = ['fecha' => now()->format('d/m/Y H:i'), 'accion' => 'Email enviado'];
                                                    $record->update(['historial_acciones' => $hist]);
                                                }
                                            })->visible(fn (?Tenant $record) => $record !== null),
                                    ])->fullWidth(),

                                    Forms\Components\ViewField::make('historial_acciones')
                                        ->view('filament.forms.components.lead-history')
                                        ->label('')
                                        ->visible(fn (?Tenant $record) => $record !== null && !empty($record->historial_acciones)),
                                ]),

                            // --- EXPEDIENTE GENERAL ---
                            Forms\Components\Tabs\Tab::make('Expediente Completo')
                                ->icon('heroicon-m-folder-open')
                                ->schema([
                                    Forms\Components\Section::make('Información Personal Complementaria')
                                        ->schema(self::getPersonaFisicaSchema())
                                        ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                                        ->columns(2),

                                    Forms\Components\Section::make('Datos del Cónyuge')
                                        ->schema(self::getConyugeSchema())
                                        ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica' && $get('estado_civil') === 'casado')
                                        ->columns(2),

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
                                ]),

                            // --- ACCESO AL SISTEMA ---
                            Forms\Components\Tabs\Tab::make('Acceso al Sistema')
                                ->icon('heroicon-m-lock-closed')
                                ->visible(fn ($record) => $record !== null) 
                                ->hidden(fn () => !auth()->user()->hasRole(['Administrador', 'Gerente', 'Asesor']))
                                ->schema([
                                    Forms\Components\Placeholder::make('estado_acceso')
                                        ->label('Estatus del Usuario')
                                        ->content(function (?Tenant $record) {
                                            if (!$record || !$record->user_id) {
                                                return new HtmlString('<span class="text-gray-500 font-bold px-3 py-1 bg-gray-100 rounded-full">Sin cuenta creada</span>');
                                            }
                                            return $record->user->is_active
                                                ? new HtmlString('<span class="text-green-600 font-bold px-3 py-1 bg-green-100 rounded-full">Activo en la plataforma</span>')
                                                : new HtmlString('<span class="text-red-600 font-bold px-3 py-1 bg-red-100 rounded-full">Inactivo (Acceso suspendido)</span>');
                                        }),

                                    Forms\Components\Actions::make([
                                        
                                        // Crear Usuario
                                        Forms\Components\Actions\Action::make('generar_acceso')
                                            ->label('Generar usuario y enviar credenciales')
                                            ->icon('heroicon-m-paper-airplane')
                                            ->color('primary')
                                            ->requiresConfirmation()
                                            ->modalDescription('El sistema generará una contraseña segura automáticamente y se la enviará al inquilino al correo registrado.')
                                            ->action(function (Tenant $record) {
                                                $password = \Illuminate\Support\Str::random(10);
                                                
                                                $user = User::firstOrCreate(
                                                    ['email' => $record->email],
                                                    [
                                                        'name' => $record->nombre_completo ?? 'Inquilino',
                                                        'password' => Hash::make($password),
                                                        'is_tenant' => true,
                                                        'is_active' => true,
                                                    ]
                                                );

                                                if ($record->user_id !== $user->id) {
                                                    $record->update(['user_id' => $user->id]);
                                                }

                                                try {
                                                    Mail::to($user->email)->send(new TenantCredentialsMail($user, $password));
                                                    Notification::make()->success()->title('Credenciales enviadas')->send();
                                                } catch (\Exception $e) {
                                                    Notification::make()->warning()->title('Usuario creado, falló correo')->body($e->getMessage())->send();
                                                }
                                            })
                                            ->visible(fn (?Tenant $record) => $record && !$record->user_id),

                                        // Desactivar Usuario
                                        Forms\Components\Actions\Action::make('desactivar_acceso')
                                            ->label('Desactivar Usuario')
                                            ->icon('heroicon-m-no-symbol')
                                            ->color('danger')
                                            ->requiresConfirmation()
                                            ->action(function (Tenant $record) {
                                                $record->user->update(['is_active' => false]);
                                                Notification::make()->success()->title('Usuario desactivado')->send();
                                            })
                                            ->visible(fn (?Tenant $record) => $record && $record->user_id && $record->user->is_active),

                                        // Reactivar Usuario
                                        Forms\Components\Actions\Action::make('activar_acceso')
                                            ->label('Reactivar Usuario')
                                            ->icon('heroicon-m-check-circle')
                                            ->color('success')
                                            ->action(function (Tenant $record) {
                                                $record->user->update(['is_active' => true]);
                                                Notification::make()->success()->title('Usuario reactivado')->send();
                                            })
                                            ->visible(fn (?Tenant $record) => $record && $record->user_id && !$record->user->is_active),
                                    ])->fullWidth(),
                                ]),
                        ]),
                    ]),
                ]),
            ]);
    }

    protected static function getPersonaFisicaSchema(): array
    {
        return [
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
            Forms\Components\TextInput::make('dominio_internet')
                ->label('Dominio de Internet')
                ->maxLength(255),

            Forms\Components\TextInput::make('rfc')
                ->label('RFC')
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                ->maxLength(13)
                ->rules(['regex:/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/i']),

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

    // CONFIGURACIÓN DE TABLA Y PERMISOS

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user->hasRole('Administrador')) {
            return $query;
        }

        if ($user->hasRole('Asesor')) {
            return $query->where('asesor_id', $user->id);
        }

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

                Tables\Columns\TextColumn::make('asesor.name') 
                    ->label('Asesor Asignado')
                    ->icon('heroicon-o-user')
                    ->placeholder('Sin Asesor')
                    ->description(fn ($record) => $record->asesor?->email)
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenants::route('/'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }
}