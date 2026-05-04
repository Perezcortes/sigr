<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OwnerResource\Pages;
use App\Filament\Resources\PropertyResource;
use App\Helpers\EstadosMexico;
use App\Models\Owner;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\TenantCredentialsMail; // Reutilizamos el correo
use App\Support\Filament\ScopesByOfficeAndAdvisor;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Actions;
use Filament\Notifications\Notification;

class OwnerResource extends Resource
{
    protected static ?string $model = Owner::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationLabel = 'Arrendadores';

    protected static ?string $modelLabel = 'Arrendador';

    protected static ?string $pluralModelLabel = 'Arrendadores';

    protected static ?string $navigationGroup = 'Rentas';

    protected static ?int $navigationSort = 3;

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
        // Administradores y Asesores pueden crear propietarios
        return auth()->user()->hasAnyRole(['Administrador', 'Asesor']);
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();

        if ($user->hasRole('Administrador')) {
            return true;
        }

        if ($user->hasRole('Asesor')) {
            // El asesor solo edita si él es el titular asignado a este propietario
            return $record->asesor_id === $user->id;
        }

        // El Gerente solo lee, no edita
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()->hasRole('Administrador');
    }

    // --- FORMULARIO REDISEÑADO A 2 COLUMNAS ---
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(3)->schema([
                    
                    // COLUMNA IZQUIERDA (Datos Generales)
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

                        Forms\Components\Section::make('Domicilio Actual')
                            ->schema(self::getDomicilioSchema())
                            ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                            ->columns(2),

                        Forms\Components\Section::make('Información de la Empresa')
                            ->schema(self::getPersonaMoralSchema())
                            ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                            ->columns(2),

                        Forms\Components\Section::make('Domicilio de la Empresa')
                            ->schema(self::getDomicilioMoralSchema())
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
                        
                        Forms\Components\Section::make('Credenciales de Acceso')
                            ->description('Zona exclusiva. Genera usuario y envía accesos al Arrendador.')
                            ->icon('heroicon-o-key')
                            ->schema(self::getCredencialesSchema()) // Usaremos el nuevo método
                            ->columns(2)
                            ->visible(fn ($record) => $record !== null)
                            ->hidden(fn () => !auth()->user()->hasRole(['Administrador', 'Gerente', 'Asesor'])),
                    ]),

                    // COLUMNA DERECHA (Acciones y Seguimiento)
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
                                                ->action(function (array $data, ?Owner $record) {
                                                    if($record) {
                                                        $hist = $record->historial_acciones ?? [];
                                                        $hist[] = ['fecha' => now()->format('d/m/Y H:i'), 'accion' => 'Nota: ' . $data['nota']];
                                                        $record->update(['historial_acciones' => $hist]);
                                                    }
                                                })->visible(fn (?Owner $record) => $record !== null),

                                            Forms\Components\Actions\Action::make('crear_cita')
                                                ->label('Cita')
                                                ->icon('heroicon-m-calendar')
                                                ->color('primary')
                                                ->form([
                                                    Forms\Components\DatePicker::make('fecha')->required(),
                                                    Forms\Components\TimePicker::make('hora')->required(),
                                                    Forms\Components\Textarea::make('observaciones'),
                                                ])
                                                ->action(function (array $data, ?Owner $record) {
                                                    if($record) {
                                                        $hist = $record->historial_acciones ?? [];
                                                        $hist[] = ['fecha' => now()->format('d/m/Y H:i'), 'accion' => "Cita: {$data['fecha']} a las {$data['hora']} - " . ($data['observaciones'] ?? '')];
                                                        $record->update(['historial_acciones' => $hist]);
                                                    }
                                                })->visible(fn (?Owner $record) => $record !== null),

                                            Forms\Components\Actions\Action::make('whatsapp')
                                                ->label('WhatsApp')
                                                ->icon('heroicon-m-chat-bubble-left-ellipsis')
                                                ->color('success')
                                                ->action(function (?Owner $record) {
                                                    if($record) {
                                                        $hist = $record->historial_acciones ?? [];
                                                        $hist[] = ['fecha' => now()->format('d/m/Y H:i'), 'accion' => 'WhatsApp iniciado'];
                                                        $record->update(['historial_acciones' => $hist]);
                                                        return redirect()->away("https://wa.me/52" . $record->telefono);
                                                    }
                                                })->visible(fn (?Owner $record) => $record !== null),

                                            Forms\Components\Actions\Action::make('registrar_llamada')
                                                ->label('Llamada')
                                                ->icon('heroicon-m-phone')
                                                ->color('gray')
                                                ->requiresConfirmation()
                                                ->action(function (?Owner $record) {
                                                    if($record) {
                                                        $hist = $record->historial_acciones ?? [];
                                                        $hist[] = ['fecha' => now()->format('d/m/Y H:i'), 'accion' => 'Llamada telefónica registrada'];
                                                        $record->update(['historial_acciones' => $hist]);
                                                    }
                                                })->visible(fn (?Owner $record) => $record !== null),
                                        ]),

                                        // Muro de historial (usa el mismo archivo blade que Inquilinos)
                                        Forms\Components\ViewField::make('historial_acciones')
                                            ->view('filament.forms.components.lead-history')
                                            ->label('')
                                            ->visible(fn (?Owner $record) => $record !== null && !empty($record->historial_acciones)),
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
                ->maxLength(255),

            Forms\Components\TextInput::make('primer_apellido')
                ->label('Apellido Paterno')
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                ->maxLength(255),

            Forms\Components\TextInput::make('segundo_apellido')
                ->label('Apellido Materno')
                ->maxLength(255),

            Forms\Components\TextInput::make('curp')
                ->label('CURP')
                ->maxLength(18)
                ->rules(['regex:/^[A-Z]{4}\d{6}[HM][A-Z]{5}[0-9A-Z]\d$/i']),

            Forms\Components\TextInput::make('email')
                ->label('Correo Electrónico')
                ->email()
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                ->unique(ignoreRecord: true)
                ->maxLength(255),

            Forms\Components\TextInput::make('telefono')
                ->label('Teléfono')
                ->tel()
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                ->maxLength(20),

            Forms\Components\Select::make('estado_civil')
                ->label('Estado Civil')
                ->options([
                    'Casado' => 'Casado',
                    'Divorciado' => 'Divorciado',
                    'Soltero' => 'Soltero',
                    'Union libre' => 'Unión libre',
                ])
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                ->live(),

            Forms\Components\Select::make('regimen_conyugal')
                ->label('En caso de ser casado bajo qué régimen')
                ->options([
                    'Sociedad conyugal' => 'Sociedad conyugal',
                    'Separacion de bienes' => 'Separación de bienes',
                ])
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica' && $get('estado_civil') === 'Casado')
                ->visible(fn (Forms\Get $get) => $get('estado_civil') === 'Casado'),

            Forms\Components\Select::make('sexo')
                ->label('Sexo')
                ->options([
                    'Masculino' => 'Masculino',
                    'Femenino' => 'Femenino',
                ])
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica'),

            Forms\Components\Select::make('nacionalidad')
                ->label('Nacionalidad')
                ->options([
                    'Mexicana' => 'Mexicana',
                    'Extranjera' => 'Extranjera',
                ])
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica'),

            Forms\Components\Select::make('tipo_identificacion')
                ->label('Identificación')
                ->options([
                    'INE' => 'INE',
                    'Pasaporte' => 'Pasaporte',
                ])
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica'),

            Forms\Components\TextInput::make('rfc')
                ->label('RFC')
                ->maxLength(13)
                ->rules(['regex:/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/i']),
        ];
    }

    protected static function getDomicilioSchema(): array
    {
        return [
            Forms\Components\TextInput::make('calle')
                ->label('Calle')
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                ->maxLength(255),

            Forms\Components\TextInput::make('numero_exterior')
                ->label('Número Exterior')
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                ->maxLength(20),

            Forms\Components\TextInput::make('numero_interior')
                ->label('Número Interior')
                ->maxLength(20),

            Forms\Components\TextInput::make('codigo_postal')
                ->label('Código Postal')
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                ->maxLength(5)
                ->rules(['regex:/^\d{5}$/']),

            Forms\Components\TextInput::make('colonia')
                ->label('Colonia')
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                ->maxLength(255),

            Forms\Components\TextInput::make('delegacion_municipio')
                ->label('Delegación / Municipio')
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                ->maxLength(255),

            Forms\Components\Select::make('estado')
                ->label('Estado')
                ->options(EstadosMexico::getEstados())
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                ->searchable(),

            Forms\Components\Textarea::make('referencias_ubicacion')
                ->label('Referencias para ubicar el domicilio')
                ->rows(3)
                ->maxLength(500),
        ];
    }

    protected static function getFormaPagoSchema(): array
    {
        return [
            Forms\Components\Select::make('forma_pago')
                ->label('Formas en la que se pagará la renta')
                ->options([
                    'Efectivo' => 'Efectivo',
                    'Transferencia' => 'Transferencia',
                    'Cheque' => 'Cheque',
                    'Otro' => 'Otro',
                ])
                ->required()
                ->live(),

            Forms\Components\TextInput::make('forma_pago_otro')
                ->label('En caso de otro, escribe el método')
                ->required(fn (Forms\Get $get) => $get('forma_pago') === 'Otro')
                ->visible(fn (Forms\Get $get) => $get('forma_pago') === 'Otro')
                ->maxLength(255),
        ];
    }

    protected static function getDatosTransferenciaSchema(): array
    {
        return [
            Forms\Components\TextInput::make('titular_cuenta')
                ->label('Titular de la cuenta')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('numero_cuenta')
                ->label('Número de cuenta')
                ->required()
                ->maxLength(50),

            Forms\Components\TextInput::make('nombre_banco')
                ->label('Nombre del banco')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('clabe_interbancaria')
                ->label('CLABE Interbancaria')
                ->required()
                ->maxLength(18)
                ->rules(['regex:/^\d{18}$/']),
        ];
    }

    protected static function getRepresentacionSchema(): array
    {
        return [
            Forms\Components\Select::make('sera_representado')
                ->label('¿El propietario será representado por un tercero para firmar el contrato?')
                ->options([
                    'No' => 'No',
                    'Si' => 'Sí',
                ])
                ->required()
                ->live(),

            Forms\Components\Select::make('tipo_representacion')
                ->label('Tipo de representación')
                ->options([
                    'Autorizacion para rentar' => 'Autorización para rentar',
                    'Mandato simple (carta poder)' => 'Mandato simple (carta poder)',
                    'Carta poder ratificada ante notario' => 'Carta poder ratificada ante notario',
                    'Poder notarial' => 'Poder notarial',
                ])
                ->required(fn (Forms\Get $get) => $get('sera_representado') === 'Si')
                ->visible(fn (Forms\Get $get) => $get('sera_representado') === 'Si'),
        ];
    }

    protected static function getRepresentanteSchema(): array
    {
        return [
            Forms\Components\TextInput::make('representante_nombres')
                ->label('Nombre(s)')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('representante_primer_apellido')
                ->label('Apellido Paterno')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('representante_segundo_apellido')
                ->label('Apellido Materno')
                ->maxLength(255),

            Forms\Components\Select::make('representante_sexo')
                ->label('Sexo')
                ->options([
                    'Masculino' => 'Masculino',
                    'Femenino' => 'Femenino',
                ])
                ->required(),

            Forms\Components\TextInput::make('representante_curp')
                ->label('CURP')
                ->maxLength(18)
                ->rules(['regex:/^[A-Z]{4}\d{6}[HM][A-Z]{5}[0-9A-Z]\d$/i']),

            Forms\Components\Select::make('representante_tipo_identificacion')
                ->label('Identificación')
                ->options([
                    'INE' => 'INE',
                    'Pasaporte' => 'Pasaporte',
                ])
                ->required(),

            Forms\Components\TextInput::make('representante_rfc')
                ->label('RFC')
                ->maxLength(13)
                ->rules(['regex:/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/i']),

            Forms\Components\TextInput::make('representante_telefono')
                ->label('Teléfono')
                ->tel()
                ->required()
                ->maxLength(20),

            Forms\Components\TextInput::make('representante_email')
                ->label('Correo Electrónico')
                ->email()
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('representante_calle')
                ->label('Calle')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('representante_numero_exterior')
                ->label('Número Exterior')
                ->required()
                ->maxLength(20),

            Forms\Components\TextInput::make('representante_numero_interior')
                ->label('Número Interior')
                ->maxLength(20),

            Forms\Components\TextInput::make('representante_cp')
                ->label('Código Postal')
                ->required()
                ->maxLength(5)
                ->rules(['regex:/^\d{5}$/']),

            Forms\Components\TextInput::make('representante_colonia')
                ->label('Colonia')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('representante_municipio')
                ->label('Delegación / Municipio')
                ->required()
                ->maxLength(255),

            Forms\Components\Select::make('representante_estado')
                ->label('Estado')
                ->options(EstadosMexico::getEstados())
                ->required()
                ->searchable(),

            Forms\Components\Textarea::make('representante_referencias')
                ->label('Referencias para ubicar el domicilio')
                ->rows(3)
                ->maxLength(500),
        ];
    }

    protected static function getPersonaMoralSchema(): array
    {
        return [
            Forms\Components\TextInput::make('razon_social')
                ->label('Nombre de la empresa')
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                ->maxLength(255),

            Forms\Components\TextInput::make('rfc')
                ->label('RFC')
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                ->maxLength(13)
                ->rules(['regex:/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/i']),

            Forms\Components\TextInput::make('email')
                ->label('Correo Electrónico')
                ->email()
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                ->unique(ignoreRecord: true)
                ->maxLength(255),

            Forms\Components\TextInput::make('telefono')
                ->label('Teléfono')
                ->tel()
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                ->maxLength(20),
        ];
    }

    protected static function getDomicilioMoralSchema(): array
    {
        return [
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

            Forms\Components\TextInput::make('codigo_postal')
                ->label('Código Postal')
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                ->maxLength(5)
                ->rules(['regex:/^\d{5}$/']),

            Forms\Components\TextInput::make('colonia')
                ->label('Colonia')
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                ->maxLength(255),

            Forms\Components\TextInput::make('delegacion_municipio')
                ->label('Delegación / Municipio')
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                ->maxLength(255),

            Forms\Components\Select::make('estado')
                ->label('Estado')
                ->options(EstadosMexico::getEstados())
                ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                ->searchable(),

            Forms\Components\Textarea::make('referencias_ubicacion')
                ->label('Referencias para ubicar la empresa')
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
                    'Masculino' => 'Masculino',
                    'Femenino' => 'Femenino',
                ]),

            Forms\Components\TextInput::make('apoderado_curp')
                ->label('CURP')
                ->maxLength(18)
                ->rules(['regex:/^[A-Z]{4}\d{6}[HM][A-Z]{5}[0-9A-Z]\d$/i']),

            Forms\Components\TextInput::make('apoderado_email')
                ->label('Correo Electrónico')
                ->email()
                ->maxLength(255),

            Forms\Components\TextInput::make('apoderado_telefono')
                ->label('Teléfono')
                ->tel()
                ->maxLength(20),

            Forms\Components\TextInput::make('apoderado_calle')
                ->label('Calle')
                ->maxLength(255),

            Forms\Components\TextInput::make('apoderado_numero_exterior')
                ->label('Número Exterior')
                ->maxLength(20),

            Forms\Components\TextInput::make('apoderado_numero_interior')
                ->label('Número Interior')
                ->maxLength(20),

            Forms\Components\TextInput::make('apoderado_cp')
                ->label('CP')
                ->maxLength(5)
                ->rules(['regex:/^\d{5}$/']),

            Forms\Components\TextInput::make('apoderado_colonia')
                ->label('Colonia')
                ->maxLength(255),

            Forms\Components\TextInput::make('apoderado_municipio')
                ->label('Municipio')
                ->maxLength(255),

            Forms\Components\Select::make('apoderado_estado')
                ->label('Estado')
                ->options(EstadosMexico::getEstados())
                ->searchable(),

            Forms\Components\Radio::make('facultades_en_acta')
                ->label('¿Sus facultades constan en el acta constitutiva de la empresa?')
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

            Forms\Components\Select::make('tipo_representacion_moral')
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

        $query->where(function (Builder $q): void {
            $q->whereNull($q->qualifyColumn('user_id'))
                ->orWhereHas('user', fn (Builder $u) => $u->where('is_owner', true));
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

                Tables\Columns\TextColumn::make('telefono')
                    ->label('Teléfono')
                    ->searchable()
                    ->default('N/A'),

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
                    ->label('Agente')
                    ->icon('heroicon-o-user-circle')
                    ->placeholder('Sin agente')      
                    ->description(fn ($record) => $record->asesor?->email) 
                    ->searchable() 
                    ->sortable()
                    ->toggleable(), 

                Tables\Columns\TextColumn::make('estado_civil')
                    ->label('Estado Civil')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'Casado' => 'Casado',
                        'Divorciado' => 'Divorciado',
                        'Soltero' => 'Soltero',
                        'Union libre' => 'Unión libre',
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
                        'Casado' => 'Casado',
                        'Divorciado' => 'Divorciado',
                        'Soltero' => 'Soltero',
                        'Union libre' => 'Unión libre',
                    ]),
            ])
            ->actions([
                // (Mantenemos tu botón personalizado de verPropiedades)
                Tables\Actions\Action::make('verPropiedades')
                    ->icon('heroicon-o-building-library')
                    ->iconButton() 
                    ->tooltip('Ver propiedades')
                    ->url(fn ($record) => PropertyResource::getUrl('index', [
                        'tableFilters' => [
                            'user_id' => [
                                'value' => $record->user_id, 
                            ],
                        ],
                    ])),
                    
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

    public static function getCredencialesSchema(): array
    {
        return [
            Forms\Components\Placeholder::make('estatus_acceso')
                ->label('Estatus de la cuenta')
                ->content(function (?Owner $record) {
                    if (!$record || !$record->user_id) {
                        return new \Illuminate\Support\HtmlString('<span style="color: gray; font-weight: bold;">Sin generar</span>');
                    }
                    return $record->user->is_active 
                        ? new \Illuminate\Support\HtmlString('<span style="color: green; font-weight: bold;">Activo</span>') 
                        : new \Illuminate\Support\HtmlString('<span style="color: red; font-weight: bold;">Inactivo</span>');
                }),

            Forms\Components\TextInput::make('login_email')
                ->label('Correo de Acceso')
                ->email()
                ->default(fn ($record) => $record?->email)
                ->disabled(fn ($record) => $record && $record->user_id)
                ->dehydrated(false),

            Forms\Components\Actions::make([
                
                // Generar (Visible si no tiene usuario)
                Action::make('enviar_accesos_owner')
                    ->label('Generar Usuario y Enviar Correo')
                    ->icon('heroicon-m-envelope')
                    ->color('primary')
                    ->visible(fn ($record) => $record && !$record->user_id)
                    ->action(function (Forms\Get $get, $record) {
                        $email = $get('login_email');
                        if (!$email) return;

                        $password = \Illuminate\Support\Str::random(10);

                        $user = User::firstOrCreate(
                            ['email' => $email],
                            [
                                'name'      => $record->nombre_completo ?? 'Arrendador',
                                'password'  => Hash::make($password),
                                'is_owner'  => true,
                                'is_active' => true,
                            ]
                        );

                        $record->update(['user_id' => $user->id]);

                        try {
                            Mail::to($user->email)->send(new TenantCredentialsMail($user, $password));
                            Notification::make()->success()->title('Credenciales enviadas')->send();
                        } catch (\Exception $e) {
                            Notification::make()->warning()->title('Usuario creado, falló el correo')->send();
                        }
                    }),

                // Desactivar (Visible si está Activo)
                Action::make('desactivar_usuario')
                    ->label('Desactivar Usuario')
                    ->icon('heroicon-m-no-symbol')
                    ->color('danger')
                    ->visible(fn ($record) => $record && $record->user_id && $record->user->is_active)
                    ->action(function ($record) {
                        $record->user->update(['is_active' => false]);
                        Notification::make()->success()->title('Arrendador desactivado')->send();
                    }),

                // Reactivar (Visible si está Inactivo)
                Action::make('activar_usuario')
                    ->label('Reactivar Usuario')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record && $record->user_id && !$record->user->is_active)
                    ->action(function ($record) {
                        $record->user->update(['is_active' => true]);
                        Notification::make()->success()->title('Arrendador reactivado')->send();
                    }),
            ])->columnSpanFull(),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOwners::route('/'),
            'edit' => Pages\EditOwner::route('/{record}/edit'),
        ];
    }
}
