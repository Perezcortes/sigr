<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GuarantorRequestResource\Pages;
use App\Models\GuarantorRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class GuarantorRequestResource extends Resource
{
    protected static ?string $model = GuarantorRequest::class;
    protected static ?string $navigationIcon = 'heroicon-o-hand-raised';
    
    // OCULTAR DEL MENÚ AL IGUAL QUE EL TENANT REQUEST
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $modelLabel = 'Solicitud de Fiador / Obligado';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Wizard::make([
                    
                    Forms\Components\Wizard\Step::make('Información Base')
                        ->icon('heroicon-o-clipboard-document-check')
                        ->schema([
                            Forms\Components\Select::make('estatus')
                                ->options(['nueva' => 'Nueva', 'en_proceso' => 'En Proceso', 'completada' => 'Completada', 'rechazada' => 'Rechazada'])
                                ->required()
                                ->default('nueva'),
                                
                            Forms\Components\Radio::make('tipo_persona')
                                ->label('Tipo de Persona')
                                ->options(['fisica' => 'Persona Física', 'moral' => 'Persona Moral'])
                                ->required()
                                ->default('fisica')
                                ->live(),
                                
                            Forms\Components\Radio::make('tipo_figura')
                                ->label('Tipo de Figura')
                                ->options(['Obligado solidario' => 'Obligado solidario', 'Fiador' => 'Fiador'])
                                ->required(),
                        ])->columns(3),

                    // PASOS EXCLUSIVOS PARA PERSONA FÍSICA
                    Forms\Components\Wizard\Step::make('Datos Personales')
                        ->icon('heroicon-o-user')
                        ->schema(self::getDatosPersonalesFisica())
                        ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                        ->columns(2),

                    Forms\Components\Wizard\Step::make('Empleo e Ingresos')
                        ->icon('heroicon-o-currency-dollar')
                        ->schema(self::getDatosEmpleoFisica())
                        ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                        ->columns(2),

                    // PASOS EXCLUSIVOS PARA PERSONA MORAL
                    Forms\Components\Wizard\Step::make('Datos de la Empresa')
                        ->icon('heroicon-o-building-office')
                        ->schema(self::getDatosEmpresaMoral())
                        ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                        ->columns(2),

                    // PASO COMPARTIDO: GARANTÍA
                    Forms\Components\Wizard\Step::make('Garantía')
                        ->description('Propiedad en garantía (Opcional)')
                        ->icon('heroicon-o-home-modern')
                        ->schema(self::getPropiedadGarantia())
                        ->columns(2),

                ])
                ->columnSpanFull()
                ->skippable()
                ->submitAction(new \Illuminate\Support\HtmlString('<button type="submit" class="fi-btn fi-btn-color-primary">Guardar Solicitud</button>')),
            ]);
    }

    // --- MÉTODOS DE ESQUEMA PRIVADOS  ---

    protected static function getDatosPersonalesFisica(): array
    {
        return [
            // SECCIÓN 1: INFORMACIÓN PERSONAL
            Forms\Components\Section::make('Datos Personales del Obligado Solidario')
                ->description('Información personal y de contacto')
                ->collapsible()
                ->schema([
                    Forms\Components\TextInput::make('nombres')
                        ->label('Nombre(s)')
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\TextInput::make('primer_apellido')
                        ->label('Apellido Paterno')
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\TextInput::make('segundo_apellido')
                        ->label('Apellido Materno'),

                    Forms\Components\DatePicker::make('fecha_nacimiento')
                        ->label('Fecha de nacimiento')
                        ->displayFormat('d/m/Y')
                        ->format('Y-m-d')
                        ->native(false)
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\Select::make('sexo')
                        ->label('Sexo')
                        ->options([
                            'Masculino' => 'Masculino',
                            'Femenino' => 'Femenino',
                        ])
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\Radio::make('nacionalidad')
                        ->label('Nacionalidad')
                        ->options([
                            'Mexicana' => 'Mexicana',
                            'Extranjera' => 'Extranjera',
                        ])
                        ->required()
                        ->live()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    // Especifique para extranjeros (Caso general sin seguro)
                    Forms\Components\TextInput::make('nacionalidad_especifica')
                        ->label('Especifique su país')
                        ->maxLength(255)
                        ->visible(fn (Forms\Get $get, ?\Illuminate\Database\Eloquent\Model $record) => 
                            $get('nacionalidad') === 'Extranjera' &&
                            !($record?->rent?->tipo_inmueble === 'residencial' && $record?->rent?->tipo_poliza === 'PÓLIZA CON SEGURO')
                        )
                        ->required(fn (Forms\Get $get, ?\Illuminate\Database\Eloquent\Model $record) => 
                            $get('nacionalidad') === 'Extranjera' &&
                            !($record?->rent?->tipo_inmueble === 'residencial' && $record?->rent?->tipo_poliza === 'PÓLIZA CON SEGURO')
                        ),

                    // --- CAMPOS EXTRANJEROS (SEGURO + RESIDENCIAL) ---
                    Forms\Components\Select::make('pais_origen')
                        ->label('País de origen')
                        ->searchable()
                        ->preload()
                        ->options(\App\Models\Pais::pluck('name', 'id')->toArray())
                        ->visible(fn (Forms\Get $get, ?\Illuminate\Database\Eloquent\Model $record) => 
                            $get('nacionalidad') === 'Extranjera' && 
                            $record?->rent?->tipo_inmueble === 'residencial' && 
                            $record?->rent?->tipo_poliza === 'PÓLIZA CON SEGURO'
                        )
                        ->required(fn (Forms\Get $get, ?\Illuminate\Database\Eloquent\Model $record) => 
                            $get('nacionalidad') === 'Extranjera' && 
                            $record?->rent?->tipo_inmueble === 'residencial' && 
                            $record?->rent?->tipo_poliza === 'PÓLIZA CON SEGURO'
                        ),

                    Forms\Components\DatePicker::make('fecha_vencimiento_tarjeta')
                        ->label('Fecha de Vencimiento de Tarjeta')
                        ->displayFormat('d/m/Y')
                        ->format('Y-m-d')
                        ->native(false)
                        ->visible(fn (Forms\Get $get, ?\Illuminate\Database\Eloquent\Model $record) => 
                            $get('nacionalidad') === 'Extranjera' && 
                            $record?->rent?->tipo_inmueble === 'residencial' && 
                            $record?->rent?->tipo_poliza === 'PÓLIZA CON SEGURO'
                        )
                        ->required(fn (Forms\Get $get, ?\Illuminate\Database\Eloquent\Model $record) => 
                            $get('nacionalidad') === 'Extranjera' && 
                            $record?->rent?->tipo_inmueble === 'residencial' && 
                            $record?->rent?->tipo_poliza === 'PÓLIZA CON SEGURO'
                        ),

                    Forms\Components\TextInput::make('nue')
                        ->label('NUE (Número Único de Extranjero)')
                        ->maxLength(50)
                        ->visible(fn (Forms\Get $get, ?\Illuminate\Database\Eloquent\Model $record) => 
                            $get('nacionalidad') === 'Extranjera' && 
                            $record?->rent?->tipo_inmueble === 'residencial' && 
                            $record?->rent?->tipo_poliza === 'PÓLIZA CON SEGURO'
                        )
                        ->required(fn (Forms\Get $get, ?\Illuminate\Database\Eloquent\Model $record) => 
                            $get('nacionalidad') === 'Extranjera' && 
                            $record?->rent?->tipo_inmueble === 'residencial' && 
                            $record?->rent?->tipo_poliza === 'PÓLIZA CON SEGURO'
                        ),

                    Forms\Components\Select::make('tipo_residencia')
                        ->label('Tipo de Residencia')
                        ->options([
                            'Permanente' => 'Permanente',
                            'Temporal' => 'Temporal',
                        ])
                        ->visible(fn (Forms\Get $get, ?\Illuminate\Database\Eloquent\Model $record) => 
                            $get('nacionalidad') === 'Extranjera' && 
                            $record?->rent?->tipo_inmueble === 'residencial' && 
                            $record?->rent?->tipo_poliza === 'PÓLIZA CON SEGURO'
                        )
                        ->required(fn (Forms\Get $get, ?\Illuminate\Database\Eloquent\Model $record) => 
                            $get('nacionalidad') === 'Extranjera' && 
                            $record?->rent?->tipo_inmueble === 'residencial' && 
                            $record?->rent?->tipo_poliza === 'PÓLIZA CON SEGURO'
                        ),

                    Forms\Components\Select::make('tipo_identificacion')
                        ->label('Identificación')
                        ->options([
                            'INE' => 'INE',
                            'Pasaporte' => 'Pasaporte',
                            'Cedula' => 'Cédula',
                            'Licencia' => 'Licencia',
                            'Otro' => 'Otro',
                        ])
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\TextInput::make('curp')
                        ->label('CURP')
                        ->maxLength(18)
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Máximo 18 caracteres.']),

                    Forms\Components\TextInput::make('rfc')
                        ->label('RFC')
                        ->maxLength(13)
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Máximo 13 caracteres.']),

                    Forms\Components\TextInput::make('email')
                        ->label('E-mail')
                        ->email()
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'email' => 'Formato no válido.']),

                    Forms\Components\TextInput::make('email_confirmacion')
                        ->label('Confirmar E-mail')
                        ->email()
                        ->same('email')
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'same' => 'Los correos no coinciden.']),

                    Forms\Components\TextInput::make('telefono_celular')
                        ->label('Celular')
                        ->tel()
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\TextInput::make('telefono_fijo')
                        ->label('Teléfono (Si no tiene, repita celular)')
                        ->tel()
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\TextInput::make('relacion_solicitante')
                        ->label('Relación con el solicitante')
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\TextInput::make('tiempo_conocerlo')
                        ->label('Tiempo de conocerlo')
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\Radio::make('estado_civil')
                        ->label('Estado civil')
                        ->options([
                            'Soltero' => 'Soltero',
                            'Casado' => 'Casado',
                        ])
                        ->required()
                        ->live()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    // Datos del Cónyuge (Solo aparece si selecciona Casado)
                    Forms\Components\Section::make('Datos del Cónyuge')
                        ->description('Información del cónyuge del obligado solidario')
                        ->schema([
                            Forms\Components\Placeholder::make('conyuge_info')
                                ->label('')
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('conyuge_nombres')
                                ->label('Nombre(s)')
                                ->required(fn (Forms\Get $get) => $get('estado_civil') === 'Casado')
                                ->validationMessages(['required' => 'Este campo es obligatorio.']),

                            Forms\Components\TextInput::make('conyuge_primer_apellido')
                                ->label('Apellido Paterno')
                                ->required(fn (Forms\Get $get) => $get('estado_civil') === 'Casado')
                                ->validationMessages(['required' => 'Este campo es obligatorio.']),

                            Forms\Components\TextInput::make('conyuge_segundo_apellido')
                                ->label('Apellido Materno'),

                            Forms\Components\TextInput::make('conyuge_telefono')
                                ->label('Teléfono')
                                ->tel()
                                ->required(fn (Forms\Get $get) => $get('estado_civil') === 'Casado')
                                ->validationMessages(['required' => 'Este campo es obligatorio.']),
                        ])
                        ->columns(2)
                        ->visible(fn (Forms\Get $get) => $get('estado_civil') === 'Casado')
                        ->columnSpanFull(),
                ])->columns(2),

            // SECCIÓN 2: DOMICILIO DONDE VIVE ACTUALMENTE
            Forms\Components\Section::make('Domicilio donde vive actualmente')
                ->description('Información de la residencia actual del obligado solidario')
                ->collapsible()
                ->schema([
                    Forms\Components\TextInput::make('calle')
                        ->label('Calle')
                        ->required()
                        ->columnSpanFull()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\TextInput::make('numero_exterior')
                        ->label('Número exterior')
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\TextInput::make('numero_interior')
                        ->label('Número interior'),

                    Forms\Components\TextInput::make('codigo_postal')
                        ->label('Código postal')
                        ->required()
                        ->maxLength(5)
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Máximo 5 dígitos.']),

                    Forms\Components\TextInput::make('colonia')
                        ->label('Colonia')
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\TextInput::make('municipio')
                        ->label('Delegación / Municipio')
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\Select::make('estado')
                        ->label('Estado')
                        ->options(\App\Helpers\EstadosMexico::getEstados())
                        ->required()
                        ->searchable()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),
                    
                    Forms\Components\TextInput::make('metros_cuadrados')
                        ->label('Número de m²')
                        ->numeric()
                        ->minValue(1)
                        ->required()
                        ->columnSpanFull()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'min' => 'El valor debe ser al menos 1.']),

                    Forms\Components\Radio::make('es_domicilio_fiscal')
                        ->label('¿Este domicilio es el mismo registrado como domicilio fiscal ante el SAT?')
                        ->options([1 => 'Sí', 0 => 'No'])
                        ->required()
                        ->live()
                        ->columnSpanFull()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    // --- DOMICILIO FISCAL ---
                    Forms\Components\Fieldset::make('Domicilio Fiscal')
                        ->visible(fn (Forms\Get $get) => (string)$get('es_domicilio_fiscal') === '0')
                        ->schema([
                            Forms\Components\TextInput::make('fiscal_calle')
                                ->label('Calle')
                                ->maxLength(200)
                                ->required()
                                ->columnSpanFull()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Máximo 200 caracteres.']),

                            Forms\Components\TextInput::make('fiscal_numero_exterior')
                                ->label('Número exterior')
                                ->maxLength(100)
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Máximo 100 caracteres.']),

                            Forms\Components\TextInput::make('fiscal_numero_interior')
                                ->label('Número interior')
                                ->maxLength(100)
                                ->validationMessages(['max' => 'Máximo 100 caracteres.']),

                            Forms\Components\TextInput::make('fiscal_codigo_postal')
                                ->label('Código postal')
                                ->length(5)
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'length' => 'Debe tener 5 dígitos.']),

                            Forms\Components\TextInput::make('fiscal_colonia')
                                ->label('Colonia')
                                ->maxLength(100)
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Máximo 100 caracteres.']),

                            Forms\Components\TextInput::make('fiscal_municipio')
                                ->label('Delegación / Municipio')
                                ->maxLength(100)
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Máximo 100 caracteres.']),

                            Forms\Components\Select::make('fiscal_estado')
                                ->label('Estado')
                                ->options(\App\Helpers\EstadosMexico::getEstados())
                                ->required()
                                ->searchable()
                                ->validationMessages(['required' => 'Este campo es obligatorio.']),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),
                ])->columns(2),
        ];
    }

    protected static function getDatosEmpleoFisica(): array
    {
        return [
            // SECCIÓN 3: DATOS LABORALES
            Forms\Components\Section::make('Datos Laborales')
                ->description('Información sobre el empleo del obligado solidario')
                ->collapsible()
                ->schema([
                    Forms\Components\TextInput::make('empresa_trabaja')
                        ->label('Empresa donde trabaja')
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\DatePicker::make('fecha_ingreso_empleo')
                        ->label('Fecha de Ingreso')
                        ->displayFormat('d/m/Y')
                        ->format('Y-m-d')
                        ->native(false)
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\TextInput::make('profesion_puesto')
                        ->label('Profesión, oficio o puesto')
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

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
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\Select::make('regimen_fiscal')
                        ->label('Régimen Fiscal')
                        ->options([
                            'Asalariado' => 'Asalariado',
                            'Actividad empresarial' => 'Actividad empresarial',
                            'Honorarios' => 'Honorarios',
                            'No aplica' => 'No aplica',
                        ])
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\TextInput::make('ingreso_mensual')
                        ->label('Ingreso mensual')
                        ->numeric()
                        ->prefix('$')
                        ->minValue(0)
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'min' => 'No se permiten valores negativos.']),
                ])->columns(2),

            // SECCIÓN 4: UBICACIÓN DE LA EMPRESA
            Forms\Components\Section::make('Ubicación de la empresa donde labora')
                ->collapsible()
                ->schema([
                    Forms\Components\TextInput::make('empresa_calle')
                        ->label('Calle')
                        ->required()
                        ->columnSpanFull()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\TextInput::make('empresa_numero_exterior')
                        ->label('Número exterior')
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\TextInput::make('empresa_numero_interior')
                        ->label('Número interior'),

                    Forms\Components\TextInput::make('empresa_codigo_postal')
                        ->label('Código postal')
                        ->required()
                        ->length(5)
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'length' => 'Debe tener 5 dígitos.']),

                    Forms\Components\TextInput::make('empresa_colonia')
                        ->label('Colonia')
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\TextInput::make('empresa_municipio')
                        ->label('Delegación / Municipio')
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\Select::make('empresa_estado')
                        ->label('Estado')
                        ->options(\App\Helpers\EstadosMexico::getEstados())
                        ->searchable()
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\TextInput::make('empresa_telefono')
                        ->label('Teléfono')
                        ->tel()
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\TextInput::make('empresa_extension')
                        ->label('No. de extensión'),
                ])->columns(2),

            // SECCIÓN 5: DECLARACIONES Y AUTORIZACIONES
            Forms\Components\Section::make('Declaraciones y Autorizaciones')
                ->schema([
                    Forms\Components\Checkbox::make('autoriza_buro')
                        ->label('Autorizo a investigar los datos por cualquier medio legal, incluyendo buró de crédito.')
                        ->accepted()
                        ->columnSpanFull()
                        ->validationMessages(['accepted' => 'Debe aceptar este término para continuar.']),

                    Forms\Components\Checkbox::make('acepta_protesta')
                        ->label('Declaro bajo protesta de decir verdad que los datos asentados son correctos y acepto que será causa de rescisión del contrato de arrendamiento la falsedad de cualquiera de ellos.')
                        ->accepted()
                        ->columnSpanFull()
                        ->validationMessages(['accepted' => 'Debe aceptar este término para continuar.']),
                ]),
        ];
    }

    protected static function getDatosEmpresaMoral(): array
    {
        return [
            // SECCIÓN 1: DATOS DE LA EMPRESA
            Forms\Components\Section::make('Información de la Empresa')
                ->description('Datos generales y fiscales de la persona moral')
                ->collapsible()
                ->schema([
                    Forms\Components\TextInput::make('razon_social')
                        ->label('Nombre de la empresa')
                        ->required()
                        ->columnSpanFull()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\TextInput::make('rfc')
                        ->label('RFC')
                        ->maxLength(13)
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Máximo 13 caracteres.']),

                    Forms\Components\Select::make('regimen_fiscal')
                        ->label('Régimen Fiscal')
                        ->options([
                            'Asalariado' => 'Asalariado',
                            'Actividad empresarial' => 'Actividad empresarial',
                            'Honorarios' => 'Honorarios',
                            'No aplica' => 'No aplica',
                        ])
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\TextInput::make('email')
                        ->label('E-Mail')
                        ->email()
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'email' => 'Formato no válido.']),

                    Forms\Components\TextInput::make('telefono')
                        ->label('Teléfono')
                        ->tel()
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\TextInput::make('antiguedad_empresa')
                        ->label('Antigüedad de la empresa')
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\TextInput::make('ingreso_mensual')
                        ->label('Ingreso mensual aproximado')
                        ->numeric()
                        ->prefix('$')
                        ->minValue(0)
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'min' => 'No se permiten valores negativos.']),

                    Forms\Components\Textarea::make('actividades_empresa')
                        ->label('Especifique breve y claramente las actividades de la Empresa y cómo obtienen sus ingresos')
                        ->rows(3)
                        ->required()
                        ->columnSpanFull()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),
                ])->columns(2),

            // SECCIÓN 2: DOMICILIO DE LA EMPRESA Y SAT
            Forms\Components\Section::make('Domicilio de la Empresa')
                ->collapsible()
                ->schema([
                    Forms\Components\TextInput::make('calle')
                        ->label('Calle')
                        ->required()
                        ->columnSpanFull()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\TextInput::make('numero_exterior')
                        ->label('Número exterior')
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\TextInput::make('numero_interior')
                        ->label('Número interior'),

                    Forms\Components\TextInput::make('codigo_postal')
                        ->label('Código postal')
                        ->length(5)
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'length' => 'Debe tener 5 dígitos.']),

                    Forms\Components\TextInput::make('colonia')
                        ->label('Colonia')
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\TextInput::make('municipio')
                        ->label('Delegación / Municipio')
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\Select::make('estado')
                        ->label('Estado')
                        ->options(\App\Helpers\EstadosMexico::getEstados())
                        ->searchable()
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\Radio::make('es_domicilio_fiscal')
                        ->label('¿Este domicilio es el mismo registrado como domicilio fiscal ante el SAT?')
                        ->options([1 => 'Sí', 0 => 'No'])
                        ->required()
                        ->live()
                        ->columnSpanFull()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    // --- DOMICILIO FISCAL ---
                    Forms\Components\Fieldset::make('Domicilio Fiscal')
                        ->visible(fn (Forms\Get $get) => (string)$get('es_domicilio_fiscal') === '0')
                        ->schema([
                            Forms\Components\TextInput::make('fiscal_calle')
                                ->label('Calle')
                                ->maxLength(200)
                                ->required()
                                ->columnSpanFull()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Máximo 200 caracteres.']),

                            Forms\Components\TextInput::make('fiscal_numero_exterior')
                                ->label('Número exterior')
                                ->maxLength(100)
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.']),

                            Forms\Components\TextInput::make('fiscal_numero_interior')
                                ->label('Número interior')
                                ->maxLength(100),

                            Forms\Components\TextInput::make('fiscal_codigo_postal')
                                ->label('Código postal')
                                ->length(5)
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'length' => 'Debe tener 5 dígitos.']),

                            Forms\Components\TextInput::make('fiscal_colonia')
                                ->label('Colonia')
                                ->maxLength(100)
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.']),

                            Forms\Components\TextInput::make('fiscal_municipio')
                                ->label('Delegación / Municipio')
                                ->maxLength(100)
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.']),

                            Forms\Components\Select::make('fiscal_estado')
                                ->label('Estado')
                                ->options(\App\Helpers\EstadosMexico::getEstados())
                                ->searchable()
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.']),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),
                ])->columns(2),

            // SECCIÓN 3: ACTA CONSTITUTIVA
            Forms\Components\Section::make('Datos del Acta Constitutiva')
                ->collapsible()
                ->schema([
                    Forms\Components\TextInput::make('notario_nombres')
                        ->label('Nombre(s) del notario')
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\TextInput::make('notario_apellidos')
                        ->label('Apellidos del notario')
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\TextInput::make('numero_escritura')
                        ->label('No. de escritura')
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\DatePicker::make('fecha_constitucion')
                        ->label('Fecha de constitución')
                        ->displayFormat('d/m/Y')
                        ->format('Y-m-d')
                        ->native(false)
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\TextInput::make('notario_numero')
                        ->label('Notario número')
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\TextInput::make('ciudad_registro')
                        ->label('Ciudad de registro')
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\Select::make('estado_registro')
                        ->label('Estado de registro')
                        ->options(\App\Helpers\EstadosMexico::getEstados())
                        ->searchable()
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\TextInput::make('numero_inscripcion_pm')
                        ->label('Número de registro o inscripción')
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\TextInput::make('giro_comercial')
                        ->label('Giro comercial')
                        ->required()
                        ->columnSpanFull()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),
                ])->columns(2),

            // SECCIÓN 4: REPRESENTANTE LEGAL Y FACULTADES
            Forms\Components\Section::make('Información sobre el Representante Legal')
                ->collapsible()
                ->schema([
                    Forms\Components\TextInput::make('rep_nombres')
                        ->label('Nombre(s)')
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\TextInput::make('rep_primer_apellido')
                        ->label('Apellido Paterno')
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\TextInput::make('rep_segundo_apellido')
                        ->label('Apellido Materno'),

                    Forms\Components\Select::make('rep_sexo')
                        ->label('Sexo')
                        ->options([
                            'Masculino' => 'Masculino',
                            'Femenino' => 'Femenino',
                        ])
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\TextInput::make('rep_rfc')
                        ->label('RFC')
                        ->maxLength(13)
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Máximo 13 caracteres.']),

                    Forms\Components\TextInput::make('rep_curp')
                        ->label('CURP')
                        ->maxLength(18)
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Máximo 18 caracteres.']),

                    Forms\Components\TextInput::make('rep_email')
                        ->label('E-Mail')
                        ->email()
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'email' => 'Formato no válido.']),

                    Forms\Components\TextInput::make('rep_telefono')
                        ->label('Teléfono')
                        ->tel()
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    // Domicilio breve del representante
                    Forms\Components\TextInput::make('rep_calle')
                        ->label('Calle Rep.')
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\TextInput::make('rep_numero_exterior')
                        ->label('Num Ext Rep.')
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\TextInput::make('rep_colonia')
                        ->label('Colonia Rep.')
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\TextInput::make('rep_codigo_postal')
                        ->label('CP Rep.')
                        ->length(5)
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'length' => 'Debe tener 5 dígitos.']),

                    // --- FACULTADES ---
                    Forms\Components\Radio::make('facultades_en_acta')
                        ->label('¿Sus facultades constan en el acta constitutiva de la empresa?')
                        ->options([1 => 'Sí', 0 => 'No'])
                        ->required()
                        ->live()
                        ->columnSpanFull()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\Group::make([
                        Forms\Components\Placeholder::make('fac_aviso')
                            ->label('')
                            ->content(new \Illuminate\Support\HtmlString('<span class="text-sm text-gray-500 italic"><span class="font-semibold text-warning-600">Nota Legal:</span> Deberá contar con facultades para obligarse a nombre de la sociedad ante terceros o para firmar contratos de arrendamiento y con facultades para otorgar y suscribir títulos de crédito.</span>'))
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('fac_escritura')
                            ->label('Escritura pública o acta número')
                            ->required()
                            ->validationMessages(['required' => 'Este campo es obligatorio.']),

                        Forms\Components\TextInput::make('fac_notario')
                            ->label('Notario número')
                            ->required()
                            ->validationMessages(['required' => 'Este campo es obligatorio.']),

                        Forms\Components\DatePicker::make('fac_fecha_escritura')
                            ->label('Fecha de escritura o acta')
                            ->displayFormat('d/m/Y')
                            ->format('Y-m-d')
                            ->native(false)
                            ->required()
                            ->validationMessages(['required' => 'Este campo es obligatorio.']),

                        Forms\Components\TextInput::make('fac_inscripcion')
                            ->label('No. de inscripción en el RP')
                            ->required()
                            ->validationMessages(['required' => 'Este campo es obligatorio.']),

                        Forms\Components\DatePicker::make('fac_fecha_inscripcion')
                            ->label('Fecha de inscripción')
                            ->displayFormat('d/m/Y')
                            ->format('Y-m-d')
                            ->native(false)
                            ->required()
                            ->validationMessages(['required' => 'Este campo es obligatorio.']),

                        Forms\Components\TextInput::make('fac_ciudad')
                            ->label('Ciudad de registro')
                            ->required()
                            ->validationMessages(['required' => 'Este campo es obligatorio.']),

                        Forms\Components\Select::make('fac_estado')
                            ->label('Estado de registro')
                            ->options(\App\Helpers\EstadosMexico::getEstados())
                            ->searchable()
                            ->required()
                            ->validationMessages(['required' => 'Este campo es obligatorio.']),

                        Forms\Components\Select::make('fac_tipo_representacion')
                            ->label('Tipo de representación')
                            ->options([
                                'Administrador único' => 'Administrador único',
                                'Presidente del consejo' => 'Presidente del consejo',
                                'Socio administrador' => 'Socio administrador',
                                'Gerente' => 'Gerente',
                                'Otro' => 'Otro',
                            ])
                            ->required()
                            ->live()
                            ->validationMessages(['required' => 'Este campo es obligatorio.']),

                        Forms\Components\TextInput::make('fac_representacion_otro')
                            ->label('Llenar en caso de otro')
                            ->maxLength(100)
                            ->required(fn (Forms\Get $get) => $get('fac_tipo_representacion') === 'Otro')
                            ->visible(fn (Forms\Get $get) => $get('fac_tipo_representacion') === 'Otro')
                            ->validationMessages(['required' => 'Este campo es obligatorio.']),
                    ])
                    ->visible(fn (Forms\Get $get) => (string)$get('facultades_en_acta') === '0')
                    ->columns(2)
                    ->columnSpanFull(),
                ])->columns(2),
        ];
    }

    protected static function getPropiedadGarantia(): array
    {
        return [
            // SECCIÓN 1: DOMICILIO DE LA PROPIEDAD
            Forms\Components\Section::make('Domicilio de la propiedad en garantía')
                ->description('Ubicación del inmueble que quedará como garantía')
                ->collapsible()
                ->schema([
                    Forms\Components\TextInput::make('garantia_calle')
                        ->label('Calle')
                        ->required()
                        ->columnSpanFull()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\TextInput::make('garantia_numero_exterior')
                        ->label('Número exterior')
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\TextInput::make('garantia_numero_interior')
                        ->label('Número interior'),

                    Forms\Components\TextInput::make('garantia_codigo_postal')
                        ->label('Código postal')
                        ->length(5)
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'length' => 'Debe tener 5 dígitos.']),

                    Forms\Components\TextInput::make('garantia_colonia')
                        ->label('Colonia')
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\TextInput::make('garantia_municipio')
                        ->label('Delegación / Municipio')
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\Select::make('garantia_estado')
                        ->label('Estado')
                        ->options(\App\Helpers\EstadosMexico::getEstados())
                        ->searchable()
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),
                ])->columns(2),

            // SECCIÓN 2: DATOS DE LA ESCRITURA
            Forms\Components\Section::make('Datos de la escritura')
                ->collapsible()
                ->schema([
                    Forms\Components\TextInput::make('garantia_num_escritura')
                        ->label('Número de escritura')
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\DatePicker::make('garantia_fecha_escritura')
                        ->label('Fecha de escritura')
                        ->displayFormat('d/m/Y')
                        ->format('Y-m-d')
                        ->native(false)
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),
                ])->columns(2),

            // SECCIÓN 3: INFORMACIÓN DEL NOTARIO
            Forms\Components\Section::make('Información del notario')
                ->collapsible()
                ->schema([
                    Forms\Components\TextInput::make('garantia_notario_nombres')
                        ->label('Nombre(s) del notario')
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\TextInput::make('garantia_notario_paterno')
                        ->label('Apellido Paterno')
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\TextInput::make('garantia_notario_materno')
                        ->label('Apellido Materno'),

                    Forms\Components\TextInput::make('garantia_num_notaria')
                        ->label('Número de notaría')
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\TextInput::make('garantia_lugar_notaria')
                        ->label('Lugar de la notaría')
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),
                ])->columns(2),

            // SECCIÓN 4: REGISTRO PÚBLICO
            Forms\Components\Section::make('Registro público de propiedad')
                ->collapsible()
                ->schema([
                    Forms\Components\TextInput::make('garantia_rpp')
                        ->label('Registro Público de la Propiedad')
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\TextInput::make('garantia_folio_real')
                        ->label('Folio real electrónico')
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\DatePicker::make('garantia_fecha_rpp')
                        ->label('Fecha')
                        ->displayFormat('d/m/Y')
                        ->format('Y-m-d')
                        ->native(false)
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\TextInput::make('garantia_boleta_predial')
                        ->label('No. de Boleta Predial')
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),
                ])->columns(2),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGuarantorRequests::route('/'),
            'edit' => Pages\EditGuarantorRequest::route('/{record}/edit'),
        ];
    }
}