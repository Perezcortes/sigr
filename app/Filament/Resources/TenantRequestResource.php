<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TenantRequestResource\Pages;
use App\Models\TenantRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TenantRequestResource extends Resource
{
    protected static ?string $model = TenantRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    // OCULTAR DEL MENÚ
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $navigationLabel = null;
    protected static ?string $modelLabel = 'Solicitud Inquilino';
    protected static ?string $pluralModelLabel = 'Solicitudes Inquilinos';

    public static function getCluster(): ?string
    {
        return null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información de la Solicitud')
                    ->schema([
                        Forms\Components\Select::make('estatus')
                            ->options([
                                'nueva' => 'Nueva',
                                'en_proceso' => 'En Proceso',
                                'completada' => 'Completada',
                                'rechazada' => 'Rechazada',
                            ])
                            ->required()
                            ->default('nueva'),
                    ])
                    ->columns(1),

                // SECCIÓN: TIPO DE PERSONA
                Forms\Components\Section::make('Tipo de Persona')
                    ->schema([
                        Forms\Components\Radio::make('tipo_persona')
                            ->label('Tipo de Persona')
                            ->options([
                                'fisica' => 'Persona Física',
                                'moral' => 'Persona Moral',
                            ])
                            ->required()
                            ->default('fisica')
                            ->live()
                            ->columnSpanFull(),
                    ]),

                // SECCIÓN: DATOS PERSONALES (Persona Física)
                Forms\Components\Section::make('Datos Personales')
                    ->description('Información personal acerca del inquilino')
                    ->schema(self::getDatosPersonalesSchema())
                    ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                    ->columns(2)
                    ->collapsible(),

                // SECCIÓN: DATOS DE EMPLEO E INGRESOS (Persona Física)
                Forms\Components\Section::make('Datos de Empleo e Ingresos')
                    ->description('Información sobre el empleo y situación económica')
                    ->schema(self::getDatosEmpleoSchema())
                    ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                    ->columns(2)
                    ->collapsible(),

                // SECCIÓN: USO DE PROPIEDAD (Persona Física)
                Forms\Components\Section::make('Uso de Propiedad')
                    ->description('Información sobre el uso que dará al inmueble')
                    ->schema(self::getUsoPropiedadSchema())
                    ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                    ->columns(2)
                    ->collapsible(),

                // SECCIÓN: REFERENCIAS (Persona Física)
                Forms\Components\Section::make('Referencias')
                    ->description('Referencias personales y familiares')
                    ->schema(self::getReferenciasPersonalesSchema())
                    ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                    ->columns(2)
                    ->collapsible(),

                // SECCIÓN: DATOS DE LA EMPRESA (Persona Moral)
                Forms\Components\Section::make('Datos de la Empresa')
                    ->description('Información acerca de la empresa')
                    ->schema(self::getDatosEmpresaSchema())
                    ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                    ->columns(2)
                    ->collapsible(),

                // SECCIÓN: DATOS Y USO DE PROPIEDAD (Persona Moral)
                Forms\Components\Section::make('Datos y Uso de Propiedad')
                    ->description('Información sobre el uso comercial del inmueble')
                    ->schema(self::getUsoPropiedadMoralSchema())
                    ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                    ->columns(2)
                    ->collapsible(),

                // SECCIÓN: REFERENCIAS COMERCIALES (Persona Moral)
                Forms\Components\Section::make('Referencias')
                    ->description('Referencias comerciales')
                    ->schema(self::getReferenciasComercialesSchema())
                    ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    protected static function getDatosPersonalesSchema(): array
    {
        return [
            // Información Personal
            Forms\Components\Group::make()
                ->schema([
                    Forms\Components\TextInput::make('nombres')
                        ->label('Nombre(s)')
                        ->required(),

                    Forms\Components\TextInput::make('primer_apellido')
                        ->label('Apellido Paterno')
                        ->required(),

                    Forms\Components\TextInput::make('segundo_apellido')
                        ->label('Apellido Materno'),

                    Forms\Components\Radio::make('nacionalidad')
                        ->label('Nacionalidad')
                        ->options([
                            'mexicana' => 'Mexicana',
                            'extranjera' => 'Otra',
                        ])
                        ->required()
                        ->live(),

                    Forms\Components\TextInput::make('nacionalidad_especifica')
                        ->label('Especifique')
                        ->required(fn (Forms\Get $get) => $get('nacionalidad') === 'extranjera')
                        ->visible(fn (Forms\Get $get) => $get('nacionalidad') === 'extranjera'),

                    Forms\Components\Radio::make('sexo')
                        ->label('Sexo')
                        ->options([
                            'masculino' => 'Masculino',
                            'femenino' => 'Femenino',
                        ])
                        ->required(),

                    Forms\Components\Radio::make('estado_civil')
                        ->label('Estado civil')
                        ->options([
                            'soltero' => 'Soltero',
                            'casado' => 'Casado',
                        ])
                        ->required()
                        ->live(),

                    Forms\Components\TextInput::make('email')
                        ->label('E-mail')
                        ->email()
                        ->required(),

                    Forms\Components\TextInput::make('email_confirmacion')
                        ->label('Confirmar E-mail')
                        ->email()
                        ->required()
                        ->same('email'),

                    Forms\Components\Select::make('tipo_identificacion')
                        ->label('Identificación')
                        ->options([
                            'INE' => 'INE',
                            'Pasaporte' => 'Pasaporte',
                            'Cedula' => 'Cédula',
                            'Licencia' => 'Licencia',
                            'Otro' => 'Otro',
                        ])
                        ->required(),

                    Forms\Components\DatePicker::make('fecha_nacimiento')
                        ->label('Fecha de nacimiento')
                        ->displayFormat('d/m/Y')
                        ->required()
                        ->native(false),

                    Forms\Components\TextInput::make('rfc')
                        ->label('RFC')
                        ->maxLength(13)
                        ->required(),

                    Forms\Components\TextInput::make('curp')
                        ->label('CURP')
                        ->maxLength(18)
                        ->required(),

                    Forms\Components\TextInput::make('telefono_celular')
                        ->label('Teléfono celular')
                        ->tel()
                        ->required(),

                    Forms\Components\TextInput::make('telefono_fijo')
                        ->label('Teléfono fijo')
                        ->tel(),
                ])
                ->columns(2)
                ->columnSpanFull(), // Permite que el grupo use todo el ancho

            // Datos del Cónyuge (solo si es casado)
            Forms\Components\Group::make()
                ->schema([
                    Forms\Components\Placeholder::make('conyuge_info')
                        ->label('Datos del Cónyuge')
                        ->content('Complete la información del cónyuge')
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('conyuge_nombres')
                        ->label('Nombre(s)')
                        ->required(),

                    Forms\Components\TextInput::make('conyuge_primer_apellido')
                        ->label('Apellido Paterno')
                        ->required(),

                    Forms\Components\TextInput::make('conyuge_segundo_apellido')
                        ->label('Apellido Materno'),

                    Forms\Components\TextInput::make('conyuge_telefono')
                        ->label('Teléfono')
                        ->tel()
                        ->required(),
                ])
                ->columns(2)
                ->visible(fn (Forms\Get $get) => $get('estado_civil') === 'casado')
                ->columnSpanFull(), // Evita que se aplaste a un lado

            // Domicilio Actual
            Forms\Components\Group::make()
                ->schema([
                    Forms\Components\Placeholder::make('domicilio_info')
                        ->label('Domicilio donde vive actualmente')
                        ->content('Complete la información de su domicilio actual')
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('calle')
                        ->label('Calle')
                        ->required()
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('numero_exterior')
                        ->label('Número exterior')
                        ->required(),

                    Forms\Components\TextInput::make('numero_interior')
                        ->label('Número interior'),

                    Forms\Components\TextInput::make('codigo_postal')
                        ->label('Código postal')
                        ->required()
                        ->maxLength(5),

                    Forms\Components\TextInput::make('colonia')
                        ->label('Colonia')
                        ->required(),

                    Forms\Components\TextInput::make('delegacion_municipio')
                        ->label('Delegación / Municipio')
                        ->required(),

                    Forms\Components\Select::make('estado')
                        ->label('Estado')
                        ->options(\App\Helpers\EstadosMexico::getEstados())
                        ->required()
                        ->searchable(),

                    Forms\Components\Select::make('situacion_habitacional')
                        ->label('Situación habitacional')
                        ->options([
                            'Inquilino' => 'Inquilino',
                            'Pension-Hotel' => 'Pensión-Hotel',
                            'Con padres o familiares' => 'Con padres o familiares',
                            'Propietario pagando' => 'Propietario pagando',
                            'Propietario liberado' => 'Propietario liberado',
                        ])
                        ->required()
                        ->live()
                        ->columnSpanFull(), 

                    // Datos del Arrendador Actual (solo si es Inquilino)
                    Forms\Components\Group::make()
                        ->schema([
                            Forms\Components\Placeholder::make('arrendador_info')
                                ->label('Datos del arrendador actual')
                                ->content('Complete la información de su arrendador actual')
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('arrendador_actual_nombres')
                                ->label('Nombre(s)')
                                ->required(),

                            Forms\Components\TextInput::make('arrendador_actual_primer_apellido')
                                ->label('Apellido Paterno')
                                ->required(),

                            Forms\Components\TextInput::make('arrendador_actual_segundo_apellido')
                                ->label('Apellido Materno'),

                            Forms\Components\TextInput::make('arrendador_actual_telefono')
                                ->label('Teléfono')
                                ->tel()
                                ->required(),

                            Forms\Components\TextInput::make('renta_actual')
                                ->label('Renta que paga actualmente')
                                ->numeric()
                                ->prefix('$')
                                ->required(),

                            Forms\Components\TextInput::make('ocupa_desde_ano')
                                ->label('Ocupa el lugar desde (año)')
                                ->numeric()
                                ->minValue(1900)
                                ->maxValue(now()->year)
                                ->required(),
                        ])
                        ->columns(2)
                        ->visible(fn (Forms\Get $get) => $get('situacion_habitacional') === 'Inquilino')
                        ->columnSpanFull(), 
                ])
                ->columns(2)
                ->columnSpanFull(), 
        ];
    }

    protected static function getDatosEmpleoSchema(): array
    {
        return [
            // Información sobre el empleo
            Forms\Components\Group::make()
                ->schema([
                    Forms\Components\Placeholder::make('empleo_info')
                        ->label('Información sobre el empleo')
                        ->content('Complete la información de su situación laboral')
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('profesion_oficio_puesto')
                        ->label('Profesión, oficio o puesto')
                        ->required(),

                    Forms\Components\Select::make('tipo_empleo')
                        ->label('Tipo de empleo')
                        ->options([
                            'Dueño de negocio' => 'Dueño de negocio',
                            'Empresario' => 'Empresario',
                            'Independiente' => 'Independiente',
                            'Empleado' => 'Empleado',
                            'Comisionista' => 'Comisionista',
                            'Jubilado' => 'Jubilado',
                        ])
                        ->required(),

                    Forms\Components\TextInput::make('telefono_empleo')
                        ->label('Teléfono')
                        ->tel()
                        ->required(),

                    Forms\Components\TextInput::make('extension_empleo')
                        ->label('No. de extensión'),

                    Forms\Components\TextInput::make('empresa_trabaja')
                        ->label('Empresa donde trabaja')
                        ->required()
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('calle_empleo')
                        ->label('Calle')
                        ->required()
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('numero_exterior_empleo')
                        ->label('Número exterior')
                        ->required(),

                    Forms\Components\TextInput::make('numero_interior_empleo')
                        ->label('Número interior'),

                    Forms\Components\TextInput::make('codigo_postal_empleo')
                        ->label('Código postal')
                        ->required()
                        ->maxLength(5),

                    Forms\Components\TextInput::make('colonia_empleo')
                        ->label('Colonia')
                        ->required(),

                    Forms\Components\TextInput::make('delegacion_municipio_empleo')
                        ->label('Delegación / Municipio')
                        ->required(),

                    Forms\Components\Select::make('estado_empleo')
                        ->label('Estado')
                        ->options(\App\Helpers\EstadosMexico::getEstados())
                        ->required()
                        ->searchable(),

                    Forms\Components\DatePicker::make('fecha_ingreso')
                        ->label('Fecha de Ingreso')
                        ->displayFormat('d/m/Y')
                        ->required()
                        ->native(false),
                ])
                ->columns(2)
                ->columnSpanFull(),

            // Jefe inmediato
            Forms\Components\Group::make()
                ->schema([
                    Forms\Components\Placeholder::make('jefe_info')
                        ->label('Jefe inmediato')
                        ->content('Complete la información de su jefe inmediato')
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('jefe_nombres')
                        ->label('Nombre (s)')
                        ->required(),

                    Forms\Components\TextInput::make('jefe_primer_apellido')
                        ->label('Apellido Paterno')
                        ->required(),

                    Forms\Components\TextInput::make('jefe_segundo_apellido')
                        ->label('Apellido Materno'),

                    Forms\Components\TextInput::make('jefe_telefono')
                        ->label('Teléfono de oficina')
                        ->tel()
                        ->required(),

                    Forms\Components\TextInput::make('jefe_extension')
                        ->label('Número de extensión'),
                ])
                ->columns(2)
                ->columnSpanFull(), // Hace que este bloque inicie una nueva fila completa

            // Ingresos
            Forms\Components\Group::make()
                ->schema([
                    Forms\Components\Placeholder::make('ingresos_info')
                        ->label('Ingresos')
                        ->content('Complete la información de sus ingresos')
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('ingreso_mensual_comprobable')
                        ->label('Ingreso mensual comprobable')
                        ->numeric()
                        ->prefix('$')
                        ->required(),

                    Forms\Components\TextInput::make('ingreso_mensual_no_comprobable')
                        ->label('Ingreso mensual no comprobable')
                        ->numeric()
                        ->prefix('$'),

                    Forms\Components\TextInput::make('numero_personas_dependen')
                        ->label('Número de personas que dependen de usted')
                        ->numeric()
                        ->minValue(0)
                        ->required()
                        ->columnSpanFull(),

                    Forms\Components\Radio::make('otra_persona_aporta')
                        ->label('¿Alguna otra persona aporta al ingreso familiar?')
                        ->options([
                            0 => 'No',
                            1 => 'Sí',
                        ])
                        ->required()
                        ->live()
                        ->columnSpanFull(),

                    // Información de persona que aporta (solo si es verdadero)
                    Forms\Components\Group::make()
                        ->schema([
                            Forms\Components\Placeholder::make('persona_aporta_info')
                                ->label('Información de la persona que aporta al ingreso familiar')
                                ->content('Complete la información de la persona que aporta')
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('numero_personas_aportan')
                                ->label('Número de personas que aportan al ingreso familiar')
                                ->numeric()
                                ->minValue(1)
                                ->required(),

                            Forms\Components\TextInput::make('persona_aporta_nombres')
                                ->label('Nombre (s)')
                                ->required(),

                            Forms\Components\TextInput::make('persona_aporta_primer_apellido')
                                ->label('Apellido Paterno')
                                ->required(),

                            Forms\Components\TextInput::make('persona_aporta_segundo_apellido')
                                ->label('Apellido Materno'),

                            Forms\Components\TextInput::make('persona_aporta_parentesco')
                                ->label('Parentesco')
                                ->required(),

                            Forms\Components\TextInput::make('persona_aporta_telefono')
                                ->label('Teléfono')
                                ->tel()
                                ->required(),

                            Forms\Components\TextInput::make('persona_aporta_empresa')
                                ->label('Empresa donde trabaja')
                                ->required(),

                            Forms\Components\TextInput::make('persona_aporta_ingreso_comprobable')
                                ->label('Ingreso mensual comprobable')
                                ->numeric()
                                ->prefix('$')
                                ->required(),
                        ])
                        ->columns(2)
                        ->visible(fn (Forms\Get $get) => $get('otra_persona_aporta') == 1)
                        ->columnSpanFull(), //Hace que el subformulario se expanda bien
                ])
                ->columns(2)
                ->columnSpanFull(),
        ];
    }

    protected static function getUsoPropiedadSchema(): array //Uso de Propiedad - Persona Física
    {
        return [
            // GRUPO 1: USO COMERCIAL
            Forms\Components\Group::make()
                ->schema([
                    Forms\Components\Placeholder::make('uso_comercial_info')
                        ->label('DATOS DEL USO COMERCIAL')
                        ->content('Complete la información sobre el uso que dará al inmueble')
                        ->columnSpanFull(),

                    Forms\Components\Select::make('tipo_inmueble_desea')
                        ->label('Tipo de inmueble que desea rentar')
                        ->options([
                            'Local' => 'Local',
                            'Oficina' => 'Oficina',
                            'Consultorio' => 'Consultorio',
                            'Bodega' => 'Bodega',
                            'Nave Industrial' => 'Nave Industrial',
                        ])
                        ->required(),

                    Forms\Components\TextInput::make('giro_negocio')
                        ->label('¿Cuál es el giro de su negocio?')
                        ->required(),

                    Forms\Components\Textarea::make('experiencia_giro')
                        ->label('Describa brevemente su experiencia en el giro')
                        ->rows(3)
                        ->required()
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('propositos_arrendamiento')
                        ->label('Propósitos del arrendamiento')
                        ->rows(3)
                        ->required()
                        ->helperText('Establecer sucursal, oficina matriz, domicilio fiscal, etc.')
                        ->columnSpanFull(),

                    Forms\Components\Radio::make('sustituye_otro_domicilio')
                        ->label('¿Este inmueble sustituirá otro domicilio?')
                        ->options([0 => 'No', 1 => 'Sí'])
                        ->required()
                        ->live()
                        ->columnSpanFull(),

                    // Domicilio anterior (Solo visible si sustituye es Sí)
                    Forms\Components\Group::make()
                        ->schema([
                            Forms\Components\Placeholder::make('dom_ant_label')->label('Información del domicilio anterior')->columnSpanFull(),
                            Forms\Components\TextInput::make('domicilio_anterior_calle')->label('Calle')->required()->columnSpanFull(),
                            Forms\Components\TextInput::make('domicilio_anterior_numero_exterior')->label('Núm Ext')->required(),
                            Forms\Components\TextInput::make('domicilio_anterior_numero_interior')->label('Núm Int'),
                            Forms\Components\TextInput::make('domicilio_anterior_codigo_postal')->label('C.P.')->required()->maxLength(5),
                            Forms\Components\TextInput::make('domicilio_anterior_colonia')->label('Colonia')->required(),
                            Forms\Components\TextInput::make('domicilio_anterior_delegacion_municipio')->label('Municipio')->required(),
                            Forms\Components\Select::make('domicilio_anterior_estado')
                                ->label('Estado')
                                ->options(\App\Helpers\EstadosMexico::getEstados())
                                ->required()
                                ->searchable(),
                            Forms\Components\Textarea::make('motivo_cambio_domicilio')->label('Motivo del cambio')->required()->columnSpanFull(),
                        ])
                        ->columns(2)
                        ->visible(fn (Forms\Get $get) => $get('sustituye_otro_domicilio') == 1)
                ])
                // Solo si la renta vinculada es 'comercial' 
                ->visible(function ($record) {
                    // Si no hay registro o renta, no mostrar
                    if (!$record || !$record->rent) return false;
                                        return $record->rent->tipo_inmueble === 'comercial'; 
                })
                ->columnSpanFull(),

            // GRUPO 2: USO RESIDENCIAL 
            Forms\Components\Group::make()
                ->schema([
                    Forms\Components\Placeholder::make('uso_residencial_info')
                        ->label('DATOS RESIDENCIALES')
                        ->content('Información sobre los habitantes y mascotas.')
                        ->columnSpanFull(),

                    Forms\Components\Section::make('Ocupantes')
                        ->schema([
                            Forms\Components\TextInput::make('numero_adultos')
                                ->label('Número de adultos que ocuparán el inmueble')
                                ->numeric()
                                ->required(),

                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('nombre_adulto_1')->label('Nombre completo adulto 1'),
                                    Forms\Components\TextInput::make('nombre_adulto_2')->label('Nombre completo adulto 2'),
                                    Forms\Components\TextInput::make('nombre_adulto_3')->label('Nombre completo adulto 3'),
                                    Forms\Components\TextInput::make('nombre_adulto_4')->label('Nombre completo adulto 4'),
                                ]),

                            Forms\Components\Radio::make('tiene_menores')
                                ->label('¿Hay menores de 18 años?')
                                ->options([0 => 'No', 1 => 'Sí'])
                                ->required()
                                ->inline()
                                ->live(),

                            Forms\Components\TextInput::make('cuantos_menores')
                                ->label('¿Cuántos?')
                                ->numeric()
                                ->required(fn (Forms\Get $get) => $get('tiene_menores') == 1)
                                ->visible(fn (Forms\Get $get) => $get('tiene_menores') == 1),
                        ]),

                    Forms\Components\Section::make('Mascotas')
                        ->schema([
                            Forms\Components\Radio::make('tiene_mascotas')
                                ->label('¿Tiene mascotas?')
                                ->options([0 => 'No', 1 => 'Sí'])
                                ->required()
                                ->inline()
                                ->live(),

                            Forms\Components\TextInput::make('especificar_mascotas')
                                ->label('Especifique (Tipo, raza, cantidad)')
                                ->placeholder('Ej: 2 perros, 1 gato')
                                ->required(fn (Forms\Get $get) => $get('tiene_mascotas') == 1)
                                ->visible(fn (Forms\Get $get) => $get('tiene_mascotas') == 1)
                                ->columnSpanFull(),
                        ]),
                ])
                // Solo si la renta vinculada es 'residencial'
                ->visible(function ($record) {
                    if (!$record || !$record->rent) return false;
                    return $record->rent->tipo_inmueble === 'residencial';
                })
                ->columnSpanFull(),
        ];
    }

    protected static function getReferenciasPersonalesSchema(): array
    {
        return [
            // Referencias Personales
            Forms\Components\Group::make()
                ->schema([
                    Forms\Components\Placeholder::make('referencias_personales_info')
                        ->label('Información sobre referencias personales')
                        ->content('Complete la información de sus referencias personales')
                        ->columnSpanFull(),

                    // Referencia 1
                    Forms\Components\Group::make()
                        ->schema([
                            Forms\Components\Placeholder::make('referencia_personal1_label')
                                ->label('Referencia personal 1')
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('referencia_personal1_nombres')
                                ->label('Nombre (s)')
                                ->required(),

                            Forms\Components\TextInput::make('referencia_personal1_primer_apellido')
                                ->label('Apellido Paterno')
                                ->required(),

                            Forms\Components\TextInput::make('referencia_personal1_segundo_apellido')
                                ->label('Apellido Materno'),

                            Forms\Components\TextInput::make('referencia_personal1_relacion')
                                ->label('Relación')
                                ->required(),

                            Forms\Components\TextInput::make('referencia_personal1_telefono')
                                ->label('Teléfono')
                                ->tel()
                                ->required(),
                        ])
                        ->columns(2)
                        ->columnSpanFull(), // Mantiene el bloque unido

                    // Referencia 2
                    Forms\Components\Group::make()
                        ->schema([
                            Forms\Components\Placeholder::make('referencia_personal2_label')
                                ->label('Referencia personal 2')
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('referencia_personal2_nombres')
                                ->label('Nombre (s)')
                                ->required(),

                            Forms\Components\TextInput::make('referencia_personal2_primer_apellido')
                                ->label('Apellido Paterno')
                                ->required(),

                            Forms\Components\TextInput::make('referencia_personal2_segundo_apellido')
                                ->label('Apellido Materno'),

                            Forms\Components\TextInput::make('referencia_personal2_relacion')
                                ->label('Relación')
                                ->required(),

                            Forms\Components\TextInput::make('referencia_personal2_telefono')
                                ->label('Teléfono')
                                ->tel()
                                ->required(),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),
                ])
                ->columns(2),

            // Referencias Familiares
            Forms\Components\Group::make()
                ->schema([
                    Forms\Components\Placeholder::make('referencias_familiares_info')
                        ->label('Información sobre referencias familiares')
                        ->content('Complete la información de sus referencias familiares')
                        ->columnSpanFull(),

                    // Familiar 1
                    Forms\Components\Group::make()
                        ->schema([
                            Forms\Components\Placeholder::make('referencia_familiar1_label')
                                ->label('Referencia familiar 1')
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('referencia_familiar1_nombres')
                                ->label('Nombre (s)')
                                ->required(),

                            Forms\Components\TextInput::make('referencia_familiar1_primer_apellido')
                                ->label('Apellido Paterno')
                                ->required(),

                            Forms\Components\TextInput::make('referencia_familiar1_segundo_apellido')
                                ->label('Apellido Materno'),

                            Forms\Components\TextInput::make('referencia_familiar1_relacion')
                                ->label('Relación')
                                ->required(),

                            Forms\Components\TextInput::make('referencia_familiar1_telefono')
                                ->label('Teléfono')
                                ->tel()
                                ->required(),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),

                    // Familiar 2
                    Forms\Components\Group::make()
                        ->schema([
                            Forms\Components\Placeholder::make('referencia_familiar2_label')
                                ->label('Referencia familiar 2')
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('referencia_familiar2_nombres')
                                ->label('Nombre (s)')
                                ->required(),

                            Forms\Components\TextInput::make('referencia_familiar2_primer_apellido')
                                ->label('Apellido Paterno')
                                ->required(),

                            Forms\Components\TextInput::make('referencia_familiar2_segundo_apellido')
                                ->label('Apellido Materno'),

                            Forms\Components\TextInput::make('referencia_familiar2_relacion')
                                ->label('Relación')
                                ->required(),

                            Forms\Components\TextInput::make('referencia_familiar2_telefono')
                                ->label('Teléfono')
                                ->tel()
                                ->required(),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ];
    }

    protected static function getDatosEmpresaSchema(): array
    {
        return [
            // Información acerca de la empresa
            Forms\Components\Group::make()
                ->schema([
                    Forms\Components\Placeholder::make('empresa_info')
                        ->label('Información acerca de la empresa')
                        ->content('Complete la información de la empresa')
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('razon_social')
                        ->label('Nombre de la empresa o razón social')
                        ->required(),

                    Forms\Components\TextInput::make('email')
                        ->label('Correo electrónico')
                        ->email()
                        ->required(),

                    Forms\Components\TextInput::make('dominio_internet')
                        ->label('Dominio de internet de la empresa'),

                    Forms\Components\TextInput::make('rfc')
                        ->label('RFC')
                        ->maxLength(13)
                        ->required(),

                    Forms\Components\TextInput::make('telefono')
                        ->label('Teléfono')
                        ->tel()
                        ->required(),

                    Forms\Components\TextInput::make('calle')
                        ->label('Calle')
                        ->required()
                        ->columnSpanFull(), 

                    Forms\Components\TextInput::make('numero_exterior')
                        ->label('Número exterior')
                        ->required(),

                    Forms\Components\TextInput::make('numero_interior')
                        ->label('Número interior'),

                    Forms\Components\TextInput::make('codigo_postal')
                        ->label('CP')
                        ->required()
                        ->maxLength(5),

                    Forms\Components\TextInput::make('colonia')
                        ->label('Colonia')
                        ->required(),

                    Forms\Components\TextInput::make('municipio')
                        ->label('Municipio')
                        ->required(),

                    Forms\Components\Select::make('estado')
                        ->label('Estado')
                        ->options(\App\Helpers\EstadosMexico::getEstados())
                        ->required()
                        ->searchable(),

                    Forms\Components\TextInput::make('ingreso_mensual_promedio')
                        ->label('Ingreso mensual promedio')
                        ->numeric()
                        ->prefix('$')
                        ->required(),

                    Forms\Components\Textarea::make('referencias_ubicacion')
                        ->label('Referencias de la ubicación')
                        ->rows(3)
                        ->required()
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),

            // Datos del acta constitutiva
            Forms\Components\Group::make()
                ->schema([
                    Forms\Components\Placeholder::make('acta_info')
                        ->label('Datos del acta constitutiva')
                        ->content('Complete la información del acta constitutiva')
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('notario_nombres')
                        ->label('Nombres(s) del notario')
                        ->required(),

                    Forms\Components\TextInput::make('notario_primer_apellido')
                        ->label('Apellido Paterno')
                        ->required(),

                    Forms\Components\TextInput::make('notario_segundo_apellido')
                        ->label('Apellido Materno'),

                    Forms\Components\TextInput::make('numero_escritura')
                        ->label('No. De escritura')
                        ->required(),

                    Forms\Components\DatePicker::make('fecha_constitucion')
                        ->label('Fecha de constitución')
                        ->displayFormat('d/m/Y')
                        ->required()
                        ->native(false),

                    Forms\Components\TextInput::make('notario_numero')
                        ->label('Notario numero')
                        ->required(),

                    Forms\Components\TextInput::make('ciudad_registro')
                        ->label('Ciudad de registro')
                        ->required(),

                    Forms\Components\Select::make('estado_registro')
                        ->label('Estado de registro')
                        ->options(\App\Helpers\EstadosMexico::getEstados())
                        ->required()
                        ->searchable(),

                    Forms\Components\TextInput::make('numero_registro_inscripcion')
                        ->label('Numero de registro o inscripción de la persona moral')
                        ->required(),

                    Forms\Components\TextInput::make('giro_comercial')
                        ->label('Giro comercial')
                        ->required(),
                ])
                ->columns(2)
                ->columnSpanFull(),

            // Apoderado legal y/o representante
            Forms\Components\Group::make()
                ->schema([
                    Forms\Components\Placeholder::make('apoderado_info')
                        ->label('Apoderado legal y/o representante')
                        ->content('Complete la información del apoderado legal')
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('apoderado_nombres')
                        ->label('Nombre (s)')
                        ->required(),

                    Forms\Components\TextInput::make('apoderado_primer_apellido')
                        ->label('Apellido Paterno')
                        ->required(),

                    Forms\Components\TextInput::make('apoderado_segundo_apellido')
                        ->label('Apellido Materno'),

                    Forms\Components\Select::make('apoderado_sexo')
                        ->label('Sexo')
                        ->options([
                            'Masculino' => 'Masculino',
                            'Femenino' => 'Femenino',
                        ])
                        ->required(),

                    Forms\Components\TextInput::make('apoderado_telefono')
                        ->label('Telefono')
                        ->tel()
                        ->required(),

                    Forms\Components\TextInput::make('apoderado_extension')
                        ->label('Extension'),

                    Forms\Components\TextInput::make('apoderado_email')
                        ->label('Correo electrónico')
                        ->email()
                        ->required(),

                    Forms\Components\Radio::make('facultades_en_acta')
                        ->label('¿Sus facultades constan en el acta constitutiva de la empresa?')
                        ->options([
                            1 => 'No', // 1 activa el formulario
                            0 => 'Sí',
                        ])
                        ->required()
                        ->live()
                        ->columnSpanFull(),

                    // Facultades en Acta (solo si es NO = 1)
                    Forms\Components\Group::make()
                        ->schema([
                            Forms\Components\Placeholder::make('facultades_info')
                                ->label('Facultades en acta')
                                ->content('Complete la información de las facultades en acta')
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('escritura_publica_numero')
                                ->label('Escritura publica o acta numero')
                                ->required(),

                            Forms\Components\TextInput::make('notario_numero_facultades')
                                ->label('Notario numero')
                                ->required(),

                            Forms\Components\DatePicker::make('fecha_escritura_facultades')
                                ->label('Fecha de escritura o acta donde consten las facultades')
                                ->displayFormat('d/m/Y')
                                ->required()
                                ->native(false),

                            Forms\Components\TextInput::make('numero_inscripcion_registro_publico')
                                ->label('No. de inscripción en el registro publico')
                                ->required(),

                            Forms\Components\TextInput::make('ciudad_registro_facultades')
                                ->label('Ciudad de registro')
                                ->required(),

                            Forms\Components\Select::make('estado_registro_facultades')
                                ->label('Estado de registro')
                                ->options(\App\Helpers\EstadosMexico::getEstados())
                                ->required()
                                ->searchable(),

                            Forms\Components\DatePicker::make('fecha_inscripcion_facultades')
                                ->label('Fecha de inscripción')
                                ->displayFormat('d/m/Y')
                                ->required()
                                ->native(false),

                            Forms\Components\Select::make('tipo_representacion')
                                ->label('Tipo de representación')
                                ->options([
                                    'Administrador único' => 'Administrador único',
                                    'Presidente del consejo' => 'Presidente del consejo',
                                    'Socio administrador' => 'Socio administrador',
                                    'Gerente' => 'Gerente',
                                    'Otro' => 'Otro',
                                ])
                                ->required()
                                ->live(),

                            Forms\Components\TextInput::make('tipo_representacion_otro')
                                ->label('Llenar en caso de otro')
                                ->required(fn (Forms\Get $get) => $get('tipo_representacion') === 'Otro')
                                ->visible(fn (Forms\Get $get) => $get('tipo_representacion') === 'Otro'),
                        ])
                        ->columns(2)
                        ->visible(fn (Forms\Get $get) => $get('facultades_en_acta') == 1)
                        ->columnSpanFull(), // Permite que el subformulario se muestre completo abajo
                ])
                ->columns(2)
                ->columnSpanFull(),
        ];
    }

    protected static function getUsoPropiedadMoralSchema(): array
    {
        return [
            Forms\Components\Group::make()
                ->schema([
                    Forms\Components\Placeholder::make('uso_comercial_info')
                        ->label('Datos del uso comercial')
                        ->content('Complete la información sobre el uso comercial del inmueble')
                        ->columnSpanFull(),

                    Forms\Components\Select::make('tipo_inmueble_desea')
                        ->label('Tipo de inmueble que desea rentar')
                        ->options([
                            'Local' => 'Local',
                            'Oficina' => 'Oficina',
                            'Consultorio' => 'Consultorio',
                            'Bodega' => 'Bodega',
                            'Nave industrial' => 'Nave industrial',
                        ])
                        ->required(),

                    Forms\Components\TextInput::make('giro_negocio')
                        ->label('¿Cuál es el giro de su negocio?')
                        ->required(),

                    Forms\Components\Textarea::make('experiencia_giro')
                        ->label('Describa brevemente su experiencia en el giro')
                        ->rows(3)
                        ->required()
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('propositos_arrendamiento')
                        ->label('Propósitos del arrendamiento')
                        ->rows(3)
                        ->required()
                        ->columnSpanFull(),

                    Forms\Components\Radio::make('sustituye_otro_domicilio')
                        ->label('¿Este inmueble sustituirá otro domicilio?')
                        ->options([
                            0 => 'No',
                            1 => 'Sí',
                        ])
                        ->required()
                        ->live()
                        ->columnSpanFull(),

                    // Información del domicilio anterior (solo si es verdadero)
                    Forms\Components\Group::make()
                        ->schema([
                            Forms\Components\Placeholder::make('domicilio_anterior_info')
                                ->label('Información del domicilio anterior')
                                ->content('Complete la información del domicilio anterior')
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('domicilio_anterior_calle')
                                ->label('Calle')
                                ->required()
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('domicilio_anterior_numero_exterior')
                                ->label('Número exterior')
                                ->required(),

                            Forms\Components\TextInput::make('domicilio_anterior_numero_interior')
                                ->label('Número interior'),

                            Forms\Components\TextInput::make('domicilio_anterior_codigo_postal')
                                ->label('Código postal')
                                ->required()
                                ->maxLength(5),

                            Forms\Components\TextInput::make('domicilio_anterior_colonia')
                                ->label('Colonia')
                                ->required(),

                            Forms\Components\TextInput::make('domicilio_anterior_delegacion_municipio')
                                ->label('Delegación / Municipio')
                                ->required(),

                            Forms\Components\Select::make('domicilio_anterior_estado')
                                ->label('Estado')
                                ->options(\App\Helpers\EstadosMexico::getEstados())
                                ->required()
                                ->searchable(),

                            Forms\Components\Textarea::make('motivo_cambio_domicilio')
                                ->label('Motivo del cambio de domicilio')
                                ->rows(3)
                                ->required()
                                ->columnSpanFull(),
                        ])
                        ->columns(2)
                        ->visible(fn (Forms\Get $get) => $get('sustituye_otro_domicilio') == 1)
                        ->columnSpanFull(), 
                ])
                ->columns(2)
                ->columnSpanFull(),
        ];
    }

    protected static function getReferenciasComercialesSchema(): array
    {
        return [
            Forms\Components\Group::make()
                ->schema([
                    Forms\Components\Placeholder::make('referencias_comerciales_info')
                        ->label('Referencias comerciales')
                        ->content('Complete la información de las referencias comerciales')
                        ->columnSpanFull(),

                    // Referencia 1
                    Forms\Components\Group::make()
                        ->schema([
                            Forms\Components\Placeholder::make('referencia_comercial1_label')
                                ->label('Referencia comercial 1')
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('referencia_comercial1_empresa')
                                ->label('Nombre de la empresa:')
                                ->required(),

                            Forms\Components\TextInput::make('referencia_comercial1_contacto')
                                ->label('Nombre del contacto')
                                ->required(),

                            Forms\Components\TextInput::make('referencia_comercial1_telefono')
                                ->label('Telefono')
                                ->tel()
                                ->required(),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),

                    // Referencia 2
                    Forms\Components\Group::make()
                        ->schema([
                            Forms\Components\Placeholder::make('referencia_comercial2_label')
                                ->label('Referencia comercial 2')
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('referencia_comercial2_empresa')
                                ->label('Nombre de la empresa:')
                                ->required(),

                            Forms\Components\TextInput::make('referencia_comercial2_contacto')
                                ->label('Nombre del contacto')
                                ->required(),

                            Forms\Components\TextInput::make('referencia_comercial2_telefono')
                                ->label('Telefono')
                                ->tel()
                                ->required(),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),

                    // Referencia 3
                    Forms\Components\Group::make()
                        ->schema([
                            Forms\Components\Placeholder::make('referencia_comercial3_label')
                                ->label('Referencia comercial 3')
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('referencia_comercial3_empresa')
                                ->label('Nombre de la empresa:')
                                ->required(),

                            Forms\Components\TextInput::make('referencia_comercial3_contacto')
                                ->label('Nombre del contacto')
                                ->required(),

                            Forms\Components\TextInput::make('referencia_comercial3_telefono')
                                ->label('Telefono')
                                ->tel()
                                ->required(),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tenant.nombre_completo')
                    ->label('Inquilino')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('rent.folio')
                    ->label('Folio Renta')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('estatus')
                    ->label('Estatus')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'nueva' => 'Nueva',
                        'en_proceso' => 'En Proceso',
                        'completada' => 'Completada',
                        'rechazada' => 'Rechazada',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'nueva' => 'gray',
                        'en_proceso' => 'warning',
                        'completada' => 'success',
                        'rechazada' => 'danger',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de Creación')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estatus')
                    ->label('Estatus')
                    ->options([
                        'nueva' => 'Nueva',
                        'en_proceso' => 'En Proceso',
                        'completada' => 'Completada',
                        'rechazada' => 'Rechazada',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListTenantRequests::route('/'),
            'edit' => Pages\EditTenantRequest::route('/{record}/edit'),
        ];
    }
}