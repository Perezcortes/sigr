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
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\TenantCredentialsMail; // Reutilizamos el correo
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Actions;
use Filament\Notifications\Notification;

class OwnerResource extends Resource
{
    protected static ?string $model = Owner::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationLabel = 'Propietarios';

    protected static ?string $modelLabel = 'Propietario';

    protected static ?string $pluralModelLabel = 'Propietarios';

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

                Forms\Components\Section::make('Domicilio Actual')
                    ->schema(self::getDomicilioSchema())
                    ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                    ->columns(2),

                Forms\Components\Section::make('Forma de Pago')
                    ->schema(self::getFormaPagoSchema())
                    ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                    ->columns(2),

                Forms\Components\Section::make('Datos de Transferencia')
                    ->schema(self::getDatosTransferenciaSchema())
                    ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica' && $get('forma_pago') === 'Transferencia')
                    ->columns(2),

                Forms\Components\Section::make('Representación')
                    ->schema(self::getRepresentacionSchema())
                    ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                    ->columns(2),

                Forms\Components\Section::make('Información del Representante')
                    ->schema(self::getRepresentanteSchema())
                    ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica' && $get('sera_representado') === 'Si')
                    ->columns(2),

                // Formulario Persona Moral
                Forms\Components\Section::make('Información de la Empresa')
                    ->schema(self::getPersonaMoralSchema())
                    ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                    ->columns(2),

                Forms\Components\Section::make('Domicilio de la Empresa')
                    ->schema(self::getDomicilioMoralSchema())
                    ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                    ->columns(2),

                Forms\Components\Section::make('Forma de Pago')
                    ->schema(self::getFormaPagoSchema())
                    ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                    ->columns(2),

                Forms\Components\Section::make('Datos de Transferencia')
                    ->schema(self::getDatosTransferenciaSchema())
                    ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'moral' && $get('forma_pago') === 'Transferencia')
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
                    ->description('Zona exclusiva. Genera usuario y envía accesos al Propietario.')
                    ->icon('heroicon-o-key')
                    ->schema(self::getCredencialesSchema())
                    ->columns(2)
                    ->visible(fn ($record) => $record !== null) // Solo en Editar
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
                Tables\Actions\Action::make('verPropiedades')
                    ->icon('heroicon-o-building-library')
                    ->iconButton() // Convierte el botón a solo icono
                    ->tooltip('Ver propiedades')
                    ->url(fn ($record) => PropertyResource::getUrl('index', [
                        'tableFilters' => [
                            'user_id' => [
                                'value' => $record->user_id, // Filtramos por el ID del Usuario vinculado al Owner
                            ],
                        ],
                    ])),
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

    public static function getCredencialesSchema(): array
    {
        return [
            Forms\Components\TextInput::make('login_email')
                ->label('Correo de Acceso (Usuario)')
                ->email()
                ->required()
                ->dehydrated(false)
                // Carga el correo del propietario al editar
                ->formatStateUsing(fn ($record) => $record->email),

            Forms\Components\TextInput::make('login_password')
                ->label('Contraseña')
                ->password()
                ->revealable()
                ->dehydrated(false)
                ->default(fn () => \Illuminate\Support\Str::random(10))
                ->helperText('Esta contraseña se encriptará. El usuario la recibirá por correo.'),

            // BOTÓN DE ACCIÓN
            Forms\Components\Actions::make([
                Action::make('enviar_accesos_owner') // ID único para evitar conflictos
                    ->label('Generar Usuario y Enviar Correo')
                    ->icon('heroicon-m-envelope')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Confirmar envío a Propietario')
                    ->modalDescription('Se creará/actualizará el usuario y se enviarán las credenciales.')
                    ->action(function (Forms\Set $set, Forms\Get $get, $record) {
                        
                        $email = $get('login_email');
                        $password = $get('login_password');

                        if (!$email || !$password) {
                            Notification::make()->danger()->title('Error')->body('Faltan datos.')->send();
                            return;
                        }

                        // CREAR / ACTUALIZAR USUARIO (Marcándolo como OWNER)
                        $user = User::updateOrCreate(
                            ['email' => $email],
                            [
                                'name'      => $record->nombre_completo ?? $record->nombres,
                                'password'  => Hash::make($password),
                                'is_owner'  => true,  
                                // 'is_tenant' => false, // Opcional: si un usuario puede ser ambos
                                'is_active' => true,
                            ]
                        );

                        // VINCULAR CON EL PROPIETARIO
                        if ($record->user_id !== $user->id) {
                            $record->update(['user_id' => $user->id]);
                        }

                        // ENVIAR CORREO
                        try {
                            // Reutilizamos el mismo diseño de correo
                            Mail::to($user->email)->send(new TenantCredentialsMail($user, $password));
                            
                            Notification::make()
                                ->success()
                                ->title('Propietario Configurado')
                                ->body("Credenciales enviadas a {$email}")
                                ->send();
                                
                        } catch (\Exception $e) {
                            Notification::make()
                                ->warning()
                                ->title('Error de envío')
                                ->body('Usuario guardado, pero falló el correo: ' . $e->getMessage())
                                ->send();
                        }
                        
                        // Limpiar password visualmente
                        $set('login_password', \Illuminate\Support\Str::random(10));
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
