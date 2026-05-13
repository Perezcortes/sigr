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
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Actions;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Mail\TenantCredentialsMail;
use App\Mail\TenantPasswordResetMail;
use App\Support\Filament\ScopesByOfficeAndAdvisor;

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

    public static function canViewAny(): bool
    {
        return auth()->user()->hasAnyRole(['Administrador', 'Gerente', 'Asesor']);
    }

    public static function canCreate(): bool
    {
        // Administradores y Asesores pueden crear inquilinos
        return auth()->user()->hasAnyRole(['Administrador', 'Asesor']);
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();

        if ($user->hasRole('Administrador')) {
            return true;
        }

        if ($user->hasRole('Asesor')) {
            // El asesor solo edita si él es el titular asignado a este inquilino
            return $record->asesor_id === $user->id;
        }

        // El Gerente solo lee, no edita
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()->hasRole('Administrador');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(3)->schema([
                    
                    // --- COLUMNA IZQUIERDA ---
                    Forms\Components\Group::make()->columnSpan(2)->schema([
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

                        Forms\Components\Section::make('Información Personal')
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
                            ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'moral' && (int)$get('facultades_en_acta') === 1)
                            ->columns(2),
                        
                        Forms\Components\Section::make('Credenciales de Acceso y Envío')
                            ->description('Administradores, gerentes y asesores asignados: generación de usuario, banderas del portal y envío de accesos.')
                            ->icon('heroicon-o-lock-closed')
                            ->schema(self::getCredencialesSchema())
                            ->columns(2)
                            ->visible(fn ($record) => $record !== null)
                            ->hidden(fn () => ! auth()->user()->hasAnyRole(['Administrador', 'Gerente', 'Asesor'])),
                    ]),

                    // --- COLUMNA DERECHA ---
                    Forms\Components\Group::make()->columnSpan(1)->schema([
                        Forms\Components\Tabs::make('CRM Tabs')
                            ->tabs([
                                // --- NOTAS Y ACCIONES ---
                                Forms\Components\Tabs\Tab::make('Notas y Seguimiento')
                                    ->icon('heroicon-m-document-text')
                                    ->schema([
                                        
                                        // Botonera de acciones
                                        Forms\Components\Actions::make([
                                            
                                            Forms\Components\Actions\Action::make('agregar_nota')
                                                ->label('Agregar Nota')
                                                ->icon('heroicon-m-pencil-square')
                                                ->color('warning')
                                                ->form([
                                                    Forms\Components\Textarea::make('nota')
                                                        ->label('Escribe aquí tu nota')
                                                        ->required()
                                                        ->rows(3),
                                                ])
                                                ->action(function (array $data, ?Tenant $record) {
                                                    if($record) {
                                                        $hist = $record->historial_acciones ?? [];
                                                        $hist[] = ['fecha' => now()->format('d/m/Y H:i'), 'accion' => 'Nota: ' . $data['nota']];
                                                        $record->update(['historial_acciones' => $hist]);
                                                    }
                                                })->visible(fn (?Tenant $record) => $record !== null),

                                            Forms\Components\Actions\Action::make('crear_cita')
                                                ->label('Cita')
                                                ->icon('heroicon-m-calendar')
                                                ->color('primary')
                                                ->form([
                                                    Forms\Components\DatePicker::make('fecha')->required(),
                                                    Forms\Components\TimePicker::make('hora')->required(),
                                                    Forms\Components\Textarea::make('observaciones'),
                                                ])
                                                ->action(function (array $data, ?Tenant $record) {
                                                    if($record) {
                                                        $hist = $record->historial_acciones ?? [];
                                                        $hist[] = ['fecha' => now()->format('d/m/Y H:i'), 'accion' => "Cita: {$data['fecha']} a las {$data['hora']} - " . ($data['observaciones'] ?? '')];
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
                                                        $hist[] = ['fecha' => now()->format('d/m/Y H:i'), 'accion' => 'Llamada telefónica registrada'];
                                                        $record->update(['historial_acciones' => $hist]);
                                                    }
                                                })->visible(fn (?Tenant $record) => $record !== null),
                                        ]),

                                        // Muro de historial
                                        Forms\Components\ViewField::make('historial_acciones')
                                            ->view('filament.forms.components.lead-history')
                                            ->label('')
                                            ->visible(fn (?Tenant $record) => $record !== null && !empty($record->historial_acciones)),
                                    ]),

                                // --- MENSAJES / WHATSAPP ---
                                Forms\Components\Tabs\Tab::make('WhatsApp')
                                    ->icon('heroicon-m-chat-bubble-bottom-center-text')
                                    ->schema([
                                        Forms\Components\Placeholder::make('info_whatsapp')
                                            ->label('Chat de WhatsApp')
                                            ->content('En la siguiente fase, conectaremos este panel con la Evolution API para ver los mensajes en vivo aquí mismo.'),
                                    ]),
                            ])
                            ->columnSpanFull(),
                    ]),
                ]),
            ]);
    }

    protected static function getPersonaFisicaSchema(): array
    {
        return [
            Forms\Components\TextInput::make('nombres')
                ->label('Nombre(s)')
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                ->maxLength(255)
                ->validationMessages([
                    'required' => 'Este campo es obligatorio.',
                    'max' => 'Este campo no puede tener más de 255 caracteres.',
                ]),

            Forms\Components\TextInput::make('primer_apellido')
                ->label('Apellido Paterno')
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                ->maxLength(100)
                ->validationMessages([
                    'required' => 'Este campo es obligatorio.',
                    'max' => 'Este campo no puede tener más de 100 caracteres.',
                ]),

            Forms\Components\TextInput::make('segundo_apellido')
                ->label('Apellido Materno')
                ->maxLength(100)
                ->validationMessages([
                    'max' => 'Este campo no puede tener más de 100 caracteres.',
                ]),

            Forms\Components\TextInput::make('email')
                ->label('E-mail')
                ->email()
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                ->unique(ignoreRecord: true)
                ->maxLength(255)
                ->validationMessages([
                    'required' => 'Este campo es obligatorio.',
                    'email' => 'Este campo no tiene un formato válido.',
                ]),

            Forms\Components\TextInput::make('telefono_celular')
                ->label('Teléfono Celular')
                ->tel()
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                ->length(10) 
                ->validationMessages([
                    'required' => 'Este campo es obligatorio.',
                    'length' => 'Este campo debe tener 10 dígitos.',
                ]),

            Forms\Components\TextInput::make('email_confirmacion')
                ->label('Confirmar E-mail')
                ->email()
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                ->same('email')
                ->maxLength(255)
                ->dehydrated(false)
                ->formatStateUsing(fn ($record) => $record?->email)
                ->validationMessages([
                    'required' => 'Este campo es obligatorio.',
                    'same' => 'Este campo no coincide con el campo E-mail.',
                ]),

            Forms\Components\Radio::make('nacionalidad')
                ->label('Nacionalidad')
                ->options([
                    'mexicana' => 'Mexicana',
                    'otra' => 'Otra', 
                ])
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                ->live()
                ->validationMessages([
                    'required' => 'Este campo es obligatorio.',
                ]),

            Forms\Components\TextInput::make('nacionalidad_especifica')
                ->label('Especifique')
                ->required(fn (Forms\Get $get) => $get('nacionalidad') === 'otra')
                ->visible(fn (Forms\Get $get) => $get('nacionalidad') === 'otra')
                ->maxLength(255)
                ->validationMessages([
                    'required' => 'Este campo es obligatorio.',
                    'max' => 'Este campo no puede tener más de 255 caracteres.',
                ]),

            Forms\Components\Radio::make('sexo')
                ->label('Sexo')
                ->options([
                    'masculino' => 'Masculino',
                    'femenino' => 'Femenino',
                ])
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                ->validationMessages([
                    'required' => 'Este campo es obligatorio.',
                ]),

            Forms\Components\Radio::make('estado_civil')
                ->label('Estado Civil')
                ->options([
                    'soltero' => 'Soltero',
                    'casado' => 'Casado',
                ])
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                ->live()
                ->validationMessages([
                    'required' => 'Este campo es obligatorio.',
                ]),

            Forms\Components\Select::make('tipo_identificacion')
                ->label('Identificación')
                ->options([
                    'INE' => 'INE',
                    'Pasaporte' => 'Pasaporte',
                    'Cedula' => 'Cédula',
                    'Licencia' => 'Licencia',
                    'Otro' => 'Otro',
                ])
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                ->validationMessages([
                    'required' => 'Este campo es obligatorio.',
                ]),

            Forms\Components\DatePicker::make('fecha_nacimiento')
                ->label('Fecha de Nacimiento')
                ->displayFormat('d/m/Y')
                ->format('Y-m-d')
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                ->native(false)
                ->validationMessages([
                    'required' => 'Este campo es obligatorio.',
                ]),

            Forms\Components\TextInput::make('rfc')
                ->label('RFC')
                ->maxLength(13)
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica') 
                ->rules(['regex:/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/i'])
                ->validationMessages([
                    'required' => 'Este campo es obligatorio.',
                ]),

            Forms\Components\TextInput::make('curp')
                ->label('CURP')
                ->length(18) 
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                ->rules(['regex:/^[A-Z]{4}\d{6}[HM][A-Z]{5}[0-9A-Z]\d$/i'])
                ->validationMessages([
                    'required' => 'Este campo es obligatorio.',
                    'length' => 'Este campo debe tener 18 caracteres.',
                ]),

            Forms\Components\TextInput::make('telefono_fijo')
                ->label('Teléfono Fijo')
                ->tel()
                ->length(10) 
                ->validationMessages([
                    'length' => 'Este campo debe tener 10 dígitos.',
                ]),
        ];
    }

    protected static function getConyugeSchema(): array
    {
        return [
            Forms\Components\TextInput::make('conyuge_nombres')
                ->label('Nombre(s)')
                ->required()
                ->maxLength(255)
                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Máximo 255 caracteres.']),

            Forms\Components\TextInput::make('conyuge_primer_apellido')
                ->label('Apellido Paterno')
                ->required()
                ->maxLength(100)
                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Máximo 100 caracteres.']),

            Forms\Components\TextInput::make('conyuge_segundo_apellido')
                ->label('Apellido Materno')
                ->maxLength(100)
                ->validationMessages(['max' => 'Máximo 100 caracteres.']),

            Forms\Components\TextInput::make('conyuge_telefono')
                ->label('Teléfono')
                ->tel()
                ->length(10) 
                ->validationMessages(['length' => 'Debe tener exactamente 10 dígitos.']),
        ];
    }

    protected static function getPersonaMoralSchema(): array
    {
        return [
            Forms\Components\Select::make('regimen_fiscal')
                ->label('Régimen Fiscal')
                ->options([
                    'Asalariado' => 'Asalariado',
                    'Actividad empresarial' => 'Actividad empresarial',
                    'Honorarios' => 'Honorarios',
                    'No aplica' => 'No aplica',
                ])
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                ->validationMessages(['required' => 'Este campo es obligatorio.']),

            Forms\Components\TextInput::make('razon_social')
                ->label('Razón Social')
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                ->maxLength(255)
                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Máximo 255 caracteres.']),

            Forms\Components\TextInput::make('email')
                ->label('Correo Electrónico')
                ->email()
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                ->unique(ignoreRecord: true)
                ->maxLength(255)
                ->validationMessages(['required' => 'Este campo es obligatorio.', 'email' => 'Formato inválido.']),

            Forms\Components\TextInput::make('dominio_internet')
                ->label('Dominio de Internet')
                ->maxLength(150) 
                ->validationMessages(['max' => 'Máximo 150 caracteres.']),

            Forms\Components\TextInput::make('rfc')
                ->label('RFC')
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                ->maxLength(13)
                ->rules(['regex:/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/i'])
                ->validationMessages(['required' => 'Este campo es obligatorio.']),

            Forms\Components\TextInput::make('telefono')
                ->label('Teléfono')
                ->tel()
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                ->length(10) 
                ->validationMessages(['required' => 'Este campo es obligatorio.', 'length' => 'Debe tener 10 dígitos.']),

            Forms\Components\TextInput::make('ingreso_mensual_promedio')
                ->label('Ingreso Mensual Promedio')
                ->numeric()
                ->minValue(0)
                ->prefix('$')
                ->validationMessages(['min' => 'No puede ser negativo.']),

            Forms\Components\Textarea::make('referencias_ubicacion')
                ->label('Referencias de Ubicación')
                ->rows(3)
                ->columnSpanFull(),

            // BLOQUE DE DOMICILIO
            Forms\Components\Fieldset::make('Domicilio Físico')
                ->schema([
                    Forms\Components\TextInput::make('calle')
                        ->label('Calle')
                        ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                        ->maxLength(200) 
                        ->columnSpanFull()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Máximo 200 caracteres.']),

                    Forms\Components\TextInput::make('numero_exterior')
                        ->label('Número Exterior')
                        ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                        ->maxLength(100) 
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Máximo 100 caracteres.']),

                    Forms\Components\TextInput::make('numero_interior')
                        ->label('Número Interior')
                        ->maxLength(100) 
                        ->validationMessages(['max' => 'Máximo 100 caracteres.']),

                    Forms\Components\TextInput::make('cp') // Pendiente revisar: 'cp' y no 'codigo_postal' 
                        ->label('Código Postal')
                        ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                        ->length(5) 
                        ->rules(['regex:/^\d{5}$/'])
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'length' => 'Debe tener 5 dígitos.']),

                    Forms\Components\TextInput::make('colonia')
                        ->label('Colonia')
                        ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                        ->maxLength(100) 
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Máximo 100 caracteres.']),

                    Forms\Components\TextInput::make('municipio')
                        ->label('Municipio')
                        ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                        ->maxLength(100) 
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Máximo 100 caracteres.']),

                    Forms\Components\Select::make('estado')
                        ->label('Estado')
                        ->options(\App\Helpers\EstadosMexico::getEstados())
                        ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                        ->searchable()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),
                ])->columns(2),

            // LÓGICA DE DOMICILIO FISCAL 
            Forms\Components\Radio::make('mismo_domicilio_fiscal')
                ->label('¿Este domicilio es el mismo registrado como domicilio fiscal ante el SAT?')
                ->options(['Si' => 'Sí', 'No' => 'No'])
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                ->live()
                ->columnSpanFull()
                ->validationMessages(['required' => 'Este campo es obligatorio.']),

            Forms\Components\Fieldset::make('Domicilio Fiscal')
                ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'moral' && $get('mismo_domicilio_fiscal') === 'No')
                ->schema([
                    Forms\Components\TextInput::make('calle_fiscal')->label('Calle')->maxLength(200)->required()->columnSpanFull(),
                    Forms\Components\TextInput::make('numero_exterior_fiscal')->label('Número exterior')->maxLength(100)->required(),
                    Forms\Components\TextInput::make('numero_interior_fiscal')->label('Número interior')->maxLength(100),
                    Forms\Components\TextInput::make('codigo_postal_fiscal')->label('Código postal')->length(5)->required(),
                    Forms\Components\TextInput::make('colonia_fiscal')->label('Colonia')->maxLength(100)->required(),
                    Forms\Components\TextInput::make('municipio_fiscal')->label('Municipio')->maxLength(100)->required(),
                    Forms\Components\Select::make('estado_fiscal')->label('Estado')->options(\App\Helpers\EstadosMexico::getEstados())->required()->searchable(),
                ])->columns(2)->columnSpanFull(),
        ];
    }

    protected static function getActaConstitutivaSchema(): array
    {
        return [
            Forms\Components\TextInput::make('notario_nombres')
                ->label('Nombre(s) del Notario')
                ->maxLength(100) 
                ->validationMessages(['max' => 'Máximo 100 caracteres.']),

            Forms\Components\TextInput::make('notario_primer_apellido')
                ->label('Apellido Paterno')
                ->maxLength(100) 
                ->validationMessages(['max' => 'Máximo 100 caracteres.']),

            Forms\Components\TextInput::make('notario_segundo_apellido')
                ->label('Apellido Materno')
                ->maxLength(100) 
                ->validationMessages(['max' => 'Máximo 100 caracteres.']),

            Forms\Components\TextInput::make('numero_escritura')
                ->label('No. de Escritura')
                ->maxLength(255),

            Forms\Components\DatePicker::make('fecha_constitucion')
                ->label('Fecha de Constitución')
                ->displayFormat('d/m/Y')
                ->format('Y-m-d')
                ->native(false),

            Forms\Components\TextInput::make('notario_numero')
                ->label('Notario Número')
                ->maxLength(255),

            Forms\Components\TextInput::make('ciudad_registro')
                ->label('Ciudad de Registro')
                ->maxLength(60) 
                ->validationMessages(['max' => 'Máximo 60 caracteres.']),

            Forms\Components\Select::make('estado_registro')
                ->label('Estado de Registro')
                ->options(\App\Helpers\EstadosMexico::getEstados())
                ->searchable(),

            Forms\Components\TextInput::make('numero_registro_inscripcion')
                ->label('Número de Registro o Inscripción')
                ->maxLength(40) 
                ->validationMessages(['max' => 'Máximo 40 caracteres.']),

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
                ->maxLength(100) 
                ->validationMessages(['max' => 'Máximo 100 caracteres.']),

            Forms\Components\TextInput::make('apoderado_segundo_apellido')
                ->label('Apellido Materno')
                ->maxLength(50) 
                ->validationMessages(['max' => 'Máximo 50 caracteres.']),

            Forms\Components\Select::make('apoderado_sexo')
                ->label('Sexo')
                ->options([
                    'Masculino' => 'Masculino',
                    'Femenino' => 'Femenino',
                ]),

            Forms\Components\TextInput::make('apoderado_telefono')
                ->label('Teléfono')
                ->tel()
                ->length(10) 
                ->validationMessages(['length' => 'Debe tener 10 dígitos.']),

            Forms\Components\TextInput::make('apoderado_extension')
                ->label('Extensión')
                ->maxLength(50) 
                ->validationMessages(['max' => 'Máximo 50 caracteres.']),

            Forms\Components\TextInput::make('apoderado_email')
                ->label('Correo Electrónico')
                ->email()
                ->maxLength(255),

            Forms\Components\Radio::make('facultades_en_acta')
                ->label('¿Sus facultades constan en el acta constitutiva?')
                ->options([
                    0 => 'Sí', 
                    1 => 'No',
                ])
                ->required()
                ->live()
                ->columnSpanFull(),

            // EL AVISO LEGAL FALTANTE
            Forms\Components\Placeholder::make('nota_facultades_tenant')
                ->label('')
                ->content(new \Illuminate\Support\HtmlString('<p class="text-sm text-gray-500 italic"><span class="font-semibold text-warning-600">Nota Legal:</span> Deberá contar con facultades para obligarse a nombre de la sociedad ante terceros o para firmar contratos de arrendamiento y con facultades para otorgar y suscribir títulos de crédito.</p>'))
                ->columnSpanFull(),
        ];
    }

    protected static function getFacultadesActaSchema(): array
    {
        return [
            Forms\Components\TextInput::make('escritura_publica_numero')
                ->label('Escritura Pública o Acta Número')
                ->required()
                ->maxLength(12) 
                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Máximo 12 caracteres.']),

            Forms\Components\TextInput::make('notario_numero_facultades')
                ->label('Notario Número')
                ->required()
                ->maxLength(100) 
                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Máximo 100 caracteres.']),

            Forms\Components\DatePicker::make('fecha_escritura_facultades')
                ->label('Fecha de Escritura o Acta')
                ->displayFormat('d/m/Y')
                ->format('Y-m-d')
                ->required()
                ->native(false)
                ->validationMessages(['required' => 'Este campo es obligatorio.']),

            Forms\Components\TextInput::make('numero_inscripcion_registro_publico')
                ->label('No. de Inscripción en el Registro Público')
                ->required()
                ->maxLength(50) 
                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Máximo 50 caracteres.']),

            Forms\Components\TextInput::make('ciudad_registro_facultades')
                ->label('Ciudad de Registro')
                ->required()
                ->maxLength(100) 
                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Máximo 100 caracteres.']),

            Forms\Components\Select::make('estado_registro_facultades')
                ->label('Estado de Registro')
                ->options(\App\Helpers\EstadosMexico::getEstados())
                ->required()
                ->searchable()
                ->validationMessages(['required' => 'Este campo es obligatorio.']),

            Forms\Components\DatePicker::make('fecha_inscripcion_facultades')
                ->label('Fecha de Inscripción')
                ->displayFormat('d/m/Y')
                ->format('Y-m-d')
                ->required()
                ->native(false)
                ->validationMessages(['required' => 'Este campo es obligatorio.']),

            Forms\Components\Select::make('tipo_representacion')
                ->label('Tipo de Representación')
                ->options([
                    'Administrador único' => 'Administrador único',
                    'Presidente del consejo' => 'Presidente del consejo',
                    'Socio administrador' => 'Socio administrador',
                    'Gerente' => 'Gerente',
                    'Otro' => 'Otro',
                ])
                ->required()
                ->validationMessages(['required' => 'Este campo es obligatorio.']),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        $query->where(function (Builder $q): void {
            $q->whereNull($q->qualifyColumn('user_id'))
                ->orWhereHas('user', fn (Builder $u) => $u->where('is_tenant', true));
        });

        return ScopesByOfficeAndAdvisor::scopeTenantOwnerIndexForFilament($query, $user);
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
            // Mostrar estado del usuario
            Forms\Components\Placeholder::make('estatus_acceso')
                ->label('Estatus de la cuenta')
                ->content(function (?Tenant $record) {
                    if (!$record || !$record->user_id) {
                        return new \Illuminate\Support\HtmlString('<span style="color: gray; font-weight: bold;">Sin generar</span>');
                    }
                    return $record->user->is_active 
                        ? new \Illuminate\Support\HtmlString('<span style="color: green; font-weight: bold;">Activo</span>') 
                        : new \Illuminate\Support\HtmlString('<span style="color: red; font-weight: bold;">Inactivo</span>');
                }),

            // Solo mostrar el email
            Forms\Components\TextInput::make('login_email')
                ->label('Correo de Acceso')
                ->email()
                ->helperText('Si aún no hay usuario portal, se usará este correo al generar la cuenta (por defecto el correo del inquilino).')
                ->default(fn (?Tenant $record) => $record?->user?->email ?? $record?->email)
                ->readOnly(fn (?Tenant $record) => (bool) ($record?->user_id))
                ->dehydrated(false)
                ->columnSpanFull(),

            Forms\Components\Grid::make(2)
                ->schema([
                    Forms\Components\Toggle::make('portal_is_tenant')
                        ->label('Es inquilino')
                        ->default(true)
                        ->inline(false),
                    Forms\Components\Toggle::make('portal_is_owner')
                        ->label('Es arrendador')
                        ->default(false)
                        ->inline(false),
                    Forms\Components\Toggle::make('portal_is_seller')
                        ->label('Es vendedor')
                        ->default(false)
                        ->inline(false),
                    Forms\Components\Toggle::make('portal_is_buyer')
                        ->label('Es comprador')
                        ->default(false)
                        ->inline(false),
                ])
                ->columnSpanFull(),

            Forms\Components\Actions::make([
                // Botón Generar (Solo visible si NO tiene usuario)
                Action::make('enviar_accesos')
                    ->label('Generar Usuario y Enviar Correo')
                    ->icon('heroicon-m-envelope')
                    ->color('primary')
                    ->visible(fn ($record) => $record && !$record->user_id)
                    ->action(function (Forms\Get $get, Tenant $record) {
                        $email = $get('login_email');
                        if (! $email) {
                            Notification::make()->warning()->title('Indica un correo de acceso')->send();

                            return;
                        }

                        $password = Str::random(10);

                        $portalFlags = [
                            'is_tenant' => (bool) ($get('portal_is_tenant') ?? true),
                            'is_owner' => (bool) ($get('portal_is_owner') ?? false),
                            'is_seller' => (bool) ($get('portal_is_seller') ?? false),
                            'is_buyer' => (bool) ($get('portal_is_buyer') ?? false),
                        ];

                        $user = User::firstOrCreate(
                            ['email' => $email],
                            [
                                'name' => $record->nombre_completo ?? 'Inquilino',
                                'password' => Hash::make($password),
                                'is_active' => true,
                                ...$portalFlags,
                            ]
                        );

                        if (! $user->wasRecentlyCreated) {
                            $user->update($portalFlags);
                            $record->update(['user_id' => $user->id]);
                            Notification::make()
                                ->info()
                                ->title('Cuenta ya existía')
                                ->body('Se asoció al inquilino y se actualizaron las banderas del portal. No se envió correo (la contraseña no se regeneró).')
                                ->send();

                            return;
                        }

                        $record->update(['user_id' => $user->id]);

                        try {
                            Mail::to($user->email)->send(new TenantCredentialsMail($user, $password));
                            Notification::make()->success()->title('Credenciales enviadas')->send();
                        } catch (\Exception $e) {
                            Notification::make()->warning()->title('Usuario creado, falló el envío')->send();
                        }
                    }),

                Action::make('restablecer_contrasena')
                    ->label('Restablecer contraseña')
                    ->icon('heroicon-m-key')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Restablecer contraseña')
                    ->modalDescription('Se generará una nueva contraseña y se enviará por correo. La contraseña actual dejará de funcionar.')
                    ->visible(fn (?Tenant $record) => (bool) ($record?->user_id))
                    ->action(function (Tenant $record) {
                        $password = Str::random(10);
                        $record->user->update(['password' => Hash::make($password)]);

                        try {
                            Mail::to($record->user->email)->send(new TenantPasswordResetMail($record->user, $password));
                            Notification::make()->success()->title('Correo enviado con la nueva contraseña')->send();
                        } catch (\Exception $e) {
                            Notification::make()->warning()->title('Contraseña actualizada; falló el envío del correo')->send();
                        }
                    }),

                // Botón Desactivar (Solo visible si está Activo)
                Action::make('desactivar_usuario')
                    ->label('Desactivar Usuario')
                    ->icon('heroicon-m-no-symbol')
                    ->color('danger')
                    ->visible(fn ($record) => $record && $record->user_id && $record->user->is_active)
                    ->action(function ($record) {
                        $record->user->update(['is_active' => false]);
                        Notification::make()->success()->title('Usuario desactivado. No podrá acceder.')->send();
                    }),

                // Botón Reactivar (Solo visible si está Inactivo)
                Action::make('activar_usuario')
                    ->label('Reactivar Usuario')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record && $record->user_id && !$record->user->is_active)
                    ->action(function ($record) {
                        $record->user->update(['is_active' => true]);
                        Notification::make()->success()->title('Usuario reactivado')->send();
                    }),
            ])->columnSpanFull(),
        ];
    }
}