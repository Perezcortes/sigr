<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OwnerRequestResource\Pages;
use App\Models\OwnerRequest;
use App\Models\Property;
use App\Models\PropertyImage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class OwnerRequestResource extends Resource
{
    protected static ?string $model = OwnerRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    // OCULTAR DEL MENÚ
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $navigationLabel = null;
    protected static ?string $modelLabel = 'Solicitud Propietario';
    protected static ?string $pluralModelLabel = 'Solicitudes Propietarios';

    public static function getCluster(): ?string
    {
        return null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Wizard::make([
                    
                    // PASO 1: Inicio
                    Forms\Components\Wizard\Step::make('Información Base')
                        ->icon('heroicon-o-clipboard-document-check')
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

                            Forms\Components\Radio::make('tipo_persona')
                                ->label('Tipo de Persona')
                                ->options([
                                    'fisica' => 'Persona Física',
                                    'moral' => 'Persona Moral',
                                ])
                                ->required()
                                ->default('fisica')
                                ->live(), // Se quitó el columnSpanFull para que queden lado a lado
                        ])->columns(2),

                    // PASOS EXCLUSIVOS PARA PERSONA FÍSICA

                    Forms\Components\Wizard\Step::make('Datos del Propietario')
                        ->description('Información personal')
                        ->icon('heroicon-o-user')
                        ->schema(self::getDatosPropietarioFisicaSchema())
                        ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                        ->columns(2),

                    Forms\Components\Wizard\Step::make('Representación')
                        ->description('Solo si será representado por un tercero')
                        ->icon('heroicon-o-users')
                        ->schema(self::getRepresentacionSchema())
                        ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                        ->columns(2),

                    // PASOS EXCLUSIVOS PARA PERSONA MORAL

                    Forms\Components\Wizard\Step::make('Datos de la Empresa')
                        ->description('Información acerca de la empresa')
                        ->icon('heroicon-o-building-office')
                        ->schema(self::getDatosEmpresaSchema())
                        ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                        ->columns(2),

                    Forms\Components\Wizard\Step::make('Documentación Legal')
                        ->description('Acta constitutiva y Apoderado')
                        ->icon('heroicon-o-scale')
                        ->schema([
                            Forms\Components\Section::make('Datos del Acta Constitutiva')
                                ->schema(self::getActaConstitutivaSchema())->columns(2),
                                
                            Forms\Components\Section::make('Apoderado Legal y/o Representante')
                                ->schema(self::getApoderadoSchema())->columns(2),
                        ])
                        ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'moral'),

                    // PASO COMPARTIDO: INMUEBLE
                    Forms\Components\Wizard\Step::make('Inmueble')
                        ->description('Datos de la propiedad a arrendar')
                        ->icon('heroicon-o-home')
                        ->schema([
                            Forms\Components\Section::make('Propiedad vinculada a la renta')
                                ->description('Selecciona una propiedad existente o da de alta una nueva.')
                                ->schema(self::getLinkedPropertySchema())
                                ->columns(1),

                            Forms\Components\Section::make('Imágenes de la Propiedad Vinculada')
                                ->schema(self::getPropertyImagesSchema())
                                ->visible(fn (Forms\Get $get) => filled($get('selected_property_id')))
                                ->columns(1),

                            Forms\Components\Section::make('Datos del Inmueble a Arrendar')
                                ->schema(self::getDatosInmuebleSchema())
                                ->visible(fn (Forms\Get $get) => blank($get('selected_property_id')))
                                ->columns(2),

                            Forms\Components\Section::make('Dirección del Inmueble')
                                ->schema(self::getDireccionInmuebleSchema())
                                ->visible(fn (Forms\Get $get) => blank($get('selected_property_id')))
                                ->columns(2),
                        ]),

                ])
                ->columnSpanFull()
                ->skippable()
                ->submitAction(new \Illuminate\Support\HtmlString('<button type="submit" class="fi-btn fi-btn-color-primary">Guardar Solicitud</button>')),
            ]);
    }

    protected static function getDatosPropietarioFisicaSchema(): array
    {
        return [
            // SECCIÓN 1: INFORMACIÓN PERSONAL
            Forms\Components\Section::make('Datos del propietario')
                ->description('Información personal acerca del propietario')
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

                    Forms\Components\TextInput::make('curp')
                        ->label('CURP')
                        ->maxLength(18)
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Máximo 18 caracteres.']),

                    Forms\Components\TextInput::make('email')
                        ->label('Correo electrónico')
                        ->email()
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'email' => 'Formato no válido.']),

                    Forms\Components\TextInput::make('telefono')
                        ->label('Teléfono')
                        ->tel()
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\Select::make('estado_civil')
                        ->label('Estado civil')
                        ->options([
                            'Casado' => 'Casado',
                            'Divorciado' => 'Divorciado',
                            'Soltero' => 'Soltero',
                            'Union libre' => 'Unión libre',
                        ])
                        ->required()
                        ->live()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\Select::make('regimen_conyugal')
                        ->label('En caso de ser casado bajo qué régimen')
                        ->options([
                            'Sociedad conyugal' => 'Sociedad conyugal',
                            'Separacion de bienes' => 'Separación de bienes',
                        ])
                        ->required(fn (Forms\Get $get) => $get('estado_civil') === 'Casado')
                        ->visible(fn (Forms\Get $get) => $get('estado_civil') === 'Casado')
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\Select::make('sexo')
                        ->label('Sexo')
                        ->options([
                            'Masculino' => 'Masculino',
                            'Femenino' => 'Femenino',
                        ])
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\DatePicker::make('fecha_nacimiento')
                        ->label('Fecha de Nacimiento')
                        ->displayFormat('d/m/Y')
                        ->format('Y-m-d')
                        ->native(false)
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\Radio::make('nacionalidad')
                        ->label('Nacionalidad')
                        ->options(['mexicana' => 'Mexicana', 'extranjera' => 'Extranjera'])
                        ->required()
                        ->live()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\Select::make('tipo_identificacion')
                        ->label('Identificación')
                        ->options([
                            'INE' => 'INE', 
                            'Pasaporte' => 'Pasaporte', 
                        ])
                        ->required()
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

                    // --- CAMPOS EXTRANJEROS (POLIZA CON SEGURO) ---
                    Forms\Components\TextInput::make('nacionalidad_especifica')
                        ->label('Especifique su país')
                        ->maxLength(255)
                        ->visible(fn (Forms\Get $get, ?\Illuminate\Database\Eloquent\Model $record) => 
                            $get('nacionalidad') === 'extranjera' && 
                            $record?->rent?->tipo_poliza !== 'PÓLIZA CON SEGURO'
                        )
                        ->required(fn (Forms\Get $get, ?\Illuminate\Database\Eloquent\Model $record) => 
                            $get('nacionalidad') === 'extranjera' && 
                            $record?->rent?->tipo_poliza !== 'PÓLIZA CON SEGURO'
                        ),

                    Forms\Components\Select::make('pais_origen')
                        ->label('País de origen')
                        ->searchable()
                        ->preload() 
                        ->options(\App\Models\Pais::pluck('name', 'id')->toArray()) 
                        ->visible(fn (Forms\Get $get, ?\Illuminate\Database\Eloquent\Model $record) => 
                            $get('nacionalidad') === 'extranjera' && 
                            $record?->rent?->tipo_poliza === 'PÓLIZA CON SEGURO'
                        )
                        ->required(fn (Forms\Get $get, ?\Illuminate\Database\Eloquent\Model $record) => 
                            $get('nacionalidad') === 'extranjera' && 
                            $record?->rent?->tipo_poliza === 'PÓLIZA CON SEGURO'
                        ),

                    Forms\Components\DatePicker::make('fecha_vencimiento_tarjeta')
                        ->label('Fecha de Vencimiento de Tarjeta')
                        ->displayFormat('d/m/Y')
                        ->format('Y-m-d')
                        ->native(false)
                        ->visible(fn (Forms\Get $get) => $get('nacionalidad') === 'extranjera')
                        ->required(fn (Forms\Get $get, ?\Illuminate\Database\Eloquent\Model $record) => 
                            $get('nacionalidad') === 'extranjera' && 
                            $record?->rent?->tipo_poliza === 'PÓLIZA CON SEGURO'
                        ),

                    Forms\Components\TextInput::make('nue')
                        ->label('NUE (Número Único de Extranjero)')
                        ->maxLength(50)
                        ->visible(fn (Forms\Get $get) => $get('nacionalidad') === 'extranjera')
                        ->required(fn (Forms\Get $get, ?\Illuminate\Database\Eloquent\Model $record) => 
                            $get('nacionalidad') === 'extranjera' && 
                            $record?->rent?->tipo_poliza === 'PÓLIZA CON SEGURO'
                        ),

                    Forms\Components\Select::make('tipo_residencia')
                        ->label('Tipo de Residencia')
                        ->options([
                            'permanente' => 'Permanente',
                            'temporal' => 'Temporal',
                        ])
                        ->visible(fn (Forms\Get $get) => $get('nacionalidad') === 'extranjera')
                        ->required(fn (Forms\Get $get, ?\Illuminate\Database\Eloquent\Model $record) => 
                            $get('nacionalidad') === 'extranjera' && 
                            $record?->rent?->tipo_poliza === 'PÓLIZA CON SEGURO'
                        ),
                ])->columns(2),

            // SECCIÓN 2: DOMICILIO ACTUAL
            Forms\Components\Section::make('Domicilio Actual')
                ->description('Información del domicilio actual del propietario')
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
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\TextInput::make('colonia')
                        ->label('Colonia')
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\TextInput::make('delegacion_municipio')
                        ->label('Delegación / Municipio')
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\Select::make('estado')
                        ->label('Estado')
                        ->options(\App\Helpers\EstadosMexico::getEstados())
                        ->required()
                        ->searchable()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\Textarea::make('referencias_ubicacion')
                        ->label('Referencias para ubicar el domicilio')
                        ->rows(3)
                        ->required()
                        ->columnSpanFull()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    // --- DOMICILIO FISCAL ---
                    Forms\Components\Radio::make('mismo_domicilio_fiscal')
                        ->label('¿Este domicilio es el mismo registrado como domicilio fiscal ante el SAT?')
                        ->options(['Si' => 'Sí', 'No' => 'No'])
                        ->required()
                        ->live()
                        ->columnSpanFull()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\Fieldset::make('Domicilio Fiscal')
                        ->visible(fn (Forms\Get $get) => $get('mismo_domicilio_fiscal') === 'No')
                        ->schema([
                            Forms\Components\TextInput::make('calle_fiscal')
                                ->label('Calle')
                                ->maxLength(200) 
                                ->required()
                                ->columnSpanFull()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 200 caracteres.']), 
                                
                            Forms\Components\TextInput::make('numero_exterior_fiscal')
                                ->label('Número exterior')
                                ->helperText('De no tener, escribir (S.N)')
                                ->maxLength(100)
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 100 caracteres.']),
                                
                            Forms\Components\TextInput::make('numero_interior_fiscal')
                                ->label('Número interior')
                                ->helperText('De no tener, escribir (S.N)')
                                ->maxLength(100)
                                ->validationMessages(['max' => 'Este campo no puede tener más de 100 caracteres.']),
                                
                            Forms\Components\TextInput::make('codigo_postal_fiscal')
                                ->label('Código postal')
                                ->length(5)
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'length' => 'Este campo debe tener 5 dígitos.']),
                                
                            Forms\Components\TextInput::make('colonia_fiscal')
                                ->label('Colonia')
                                ->maxLength(100) 
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 100 caracteres.']),
                                
                            Forms\Components\TextInput::make('municipio_fiscal')
                                ->label('Delegación / Municipio')
                                ->maxLength(100) 
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 100 caracteres.']),
                                
                            Forms\Components\Select::make('estado_fiscal')
                                ->label('Estado')
                                ->options(\App\Helpers\EstadosMexico::getEstados())
                                ->required()
                                ->searchable()
                                ->validationMessages(['required' => 'Este campo es obligatorio.']),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),
                ])->columns(2),

            // SECCIÓN 3: FORMA DE PAGO DE RENTA
            Forms\Components\Section::make('Forma de pago de renta')
                ->description('Defina cómo recibirá las rentas mensuales')
                ->collapsible()
                ->schema([
                    Forms\Components\Select::make('forma_pago')
                        ->label('Forma de pago de la renta')
                        ->options([
                            'Efectivo' => 'Efectivo',
                            'Transferencia' => 'Transferencia',
                            'Cheque' => 'Cheque',
                            'Otro' => 'Otro',
                        ])
                        ->required()
                        ->live()
                        ->columnSpanFull()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\TextInput::make('forma_pago_otro')
                        ->label('En caso de otro, especifique')
                        ->required(fn (Forms\Get $get) => $get('forma_pago') === 'Otro')
                        ->visible(fn (Forms\Get $get) => $get('forma_pago') === 'Otro')
                        ->columnSpanFull()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\Grid::make(2)
                        ->visible(fn (Forms\Get $get) => $get('forma_pago') === 'Transferencia')
                        ->schema([
                            Forms\Components\TextInput::make('titular_cuenta')
                                ->label('Titular de la cuenta')
                                ->required(fn (Forms\Get $get) => $get('forma_pago') === 'Transferencia')
                                ->validationMessages(['required' => 'Este campo es obligatorio.']),

                            Forms\Components\TextInput::make('numero_cuenta')
                                ->label('Número de cuenta')
                                ->required(fn (Forms\Get $get) => $get('forma_pago') === 'Transferencia')
                                ->validationMessages(['required' => 'Este campo es obligatorio.']),

                            Forms\Components\TextInput::make('nombre_banco')
                                ->label('Nombre del banco')
                                ->required(fn (Forms\Get $get) => $get('forma_pago') === 'Transferencia')
                                ->validationMessages(['required' => 'Este campo es obligatorio.']),

                            Forms\Components\TextInput::make('clabe_interbancaria')
                                ->label('CLABE interbancaria')
                                ->maxLength(18)
                                ->required(fn (Forms\Get $get) => $get('forma_pago') === 'Transferencia')
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Máximo 18 caracteres.']),
                        ]),
                ])->columns(2),
        ];
    }

    protected static function getDatosEmpresaSchema(): array
    {
        return [
            // SECCIÓN 1: DATOS FISCALES DE LA EMPRESA
            Forms\Components\Section::make('Información acerca de la empresa')
                ->collapsible()
                ->schema([
                    Forms\Components\TextInput::make('razon_social')
                        ->label('Nombre de la empresa o razón social')
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\TextInput::make('rfc_moral')
                        ->statePath('rfc')
                        ->label('RFC')
                        ->maxLength(13)
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Máximo 13 caracteres.']),

                    Forms\Components\Select::make('regimen_fiscal_moral')
                        ->statePath('regimen_fiscal')
                        ->label('Régimen Fiscal')
                        ->options([
                            'Asalariado' => 'Asalariado',
                            'Actividad empresarial' => 'Actividad empresarial',
                            'Honorarios' => 'Honorarios',
                            'No aplica' => 'No aplica',
                        ])
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\TextInput::make('email_moral')
                        ->statePath('email')
                        ->label('Correo Electrónico')
                        ->email()
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'email' => 'Formato no válido.']),

                    Forms\Components\TextInput::make('telefono_moral')
                        ->statePath('telefono')
                        ->label('Teléfono')
                        ->tel()
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),
                ])->columns(2),

            // SECCIÓN 2: UBICACIÓN DE LA SOCIEDAD Y SAT
            Forms\Components\Section::make('Domicilio actual de la empresa')
                ->collapsible()
                ->schema([
                    Forms\Components\TextInput::make('calle_moral')
                        ->statePath('calle')
                        ->label('Calle')
                        ->required()
                        ->columnSpanFull()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\TextInput::make('numero_exterior_moral')
                        ->statePath('numero_exterior')
                        ->label('Número exterior')
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\TextInput::make('numero_interior_moral')
                        ->statePath('numero_interior')
                        ->label('Número interior'),

                    Forms\Components\TextInput::make('codigo_postal_moral')
                        ->statePath('codigo_postal')
                        ->label('Código postal')
                        ->required()
                        ->maxLength(5)
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\TextInput::make('colonia_moral')
                        ->statePath('colonia')
                        ->label('Colonia')
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\TextInput::make('delegacion_municipio_moral')
                        ->statePath('delegacion_municipio')
                        ->label('Delegación / Municipio')
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\Select::make('estado_moral')
                        ->statePath('estado')
                        ->label('Estado')
                        ->options(\App\Helpers\EstadosMexico::getEstados())
                        ->required()
                        ->searchable()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\Textarea::make('referencias_ubicacion_moral')
                        ->statePath('referencias_ubicacion')
                        ->label('Referencias para ubicar la empresa')
                        ->rows(3)
                        ->required()
                        ->columnSpanFull()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    // --- DOMICILIO FISCAL EMPRESA ---
                    Forms\Components\Radio::make('mismo_domicilio_fiscal_moral')
                        ->statePath('mismo_domicilio_fiscal')
                        ->label('¿Este domicilio es el mismo registrado como domicilio fiscal ante el SAT?')
                        ->options(['Si' => 'Sí', 'No' => 'No'])
                        ->required()
                        ->live()
                        ->columnSpanFull()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\Fieldset::make('Domicilio Fiscal')
                        ->visible(fn (Forms\Get $get) => $get('mismo_domicilio_fiscal') === 'No')
                        ->schema([
                            Forms\Components\TextInput::make('calle_fiscal_moral')
                                ->statePath('calle_fiscal')
                                ->label('Calle')
                                ->maxLength(200) 
                                ->required()
                                ->columnSpanFull()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Máximo 200 caracteres.']), 
                                
                            Forms\Components\TextInput::make('numero_exterior_fiscal_moral')
                                ->statePath('numero_exterior_fiscal')
                                ->label('Número exterior')
                                ->helperText('De no tener, escribir (S.N)')
                                ->maxLength(100)
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Máximo 100 caracteres.']),
                                
                            Forms\Components\TextInput::make('numero_interior_fiscal_moral')
                                ->statePath('numero_interior_fiscal')
                                ->label('Número interior')
                                ->helperText('De no tener, escribir (S.N)')
                                ->maxLength(100)
                                ->validationMessages(['max' => 'Máximo 100 caracteres.']),
                                
                            Forms\Components\TextInput::make('codigo_postal_fiscal_moral')
                                ->statePath('codigo_postal_fiscal')
                                ->label('Código postal')
                                ->length(5)
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'length' => 'Debe tener 5 dígitos.']),
                                
                            Forms\Components\TextInput::make('colonia_fiscal_moral')
                                ->statePath('colonia_fiscal')
                                ->label('Colonia')
                                ->maxLength(100) 
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Máximo 100 caracteres.']),
                                
                            Forms\Components\TextInput::make('municipio_fiscal_moral')
                                ->statePath('municipio_fiscal')
                                ->label('Delegación / Municipio')
                                ->maxLength(100) 
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Máximo 100 caracteres.']),
                                
                            Forms\Components\Select::make('estado_fiscal_moral')
                                ->statePath('estado_fiscal')
                                ->label('Estado')
                                ->options(\App\Helpers\EstadosMexico::getEstados())
                                ->required()
                                ->searchable()
                                ->validationMessages(['required' => 'Este campo es obligatorio.']),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),
                ])->columns(2),

            // SECCIÓN 3: FORMA DE COBRO EMPRESA
            Forms\Components\Section::make('Forma de Pago de la Renta')
                ->collapsible()
                ->schema([
                    Forms\Components\Select::make('forma_pago_moral')
                        ->statePath('forma_pago')
                        ->label('Forma de pago de la renta')
                        ->options([
                            'Efectivo' => 'Efectivo',
                            'Transferencia' => 'Transferencia',
                            'Cheque' => 'Cheque',
                            'Otro' => 'Otro',
                        ])
                        ->required()
                        ->live()
                        ->columnSpanFull()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\TextInput::make('forma_pago_otro_moral')
                        ->statePath('forma_pago_otro')
                        ->label('En caso de otro, especifique')
                        ->required(fn (Forms\Get $get) => $get('forma_pago') === 'Otro')
                        ->visible(fn (Forms\Get $get) => $get('forma_pago') === 'Otro')
                        ->columnSpanFull()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\Grid::make(2)
                        ->visible(fn (Forms\Get $get) => $get('forma_pago') === 'Transferencia')
                        ->schema([
                            Forms\Components\TextInput::make('titular_cuenta_moral')
                                ->statePath('titular_cuenta')
                                ->label('Titular de la cuenta')
                                ->required(fn (Forms\Get $get) => $get('forma_pago') === 'Transferencia')
                                ->validationMessages(['required' => 'Este campo es obligatorio.']),

                            Forms\Components\TextInput::make('numero_cuenta_moral')
                                ->statePath('numero_cuenta')
                                ->label('Número de cuenta')
                                ->required(fn (Forms\Get $get) => $get('forma_pago') === 'Transferencia')
                                ->validationMessages(['required' => 'Este campo es obligatorio.']),

                            Forms\Components\TextInput::make('nombre_banco_moral')
                                ->statePath('nombre_banco')
                                ->label('Nombre del banco')
                                ->required(fn (Forms\Get $get) => $get('forma_pago') === 'Transferencia')
                                ->validationMessages(['required' => 'Este campo es obligatorio.']),

                            Forms\Components\TextInput::make('clabe_interbancaria_moral')
                                ->statePath('clabe_interbancaria')
                                ->label('CLABE interbancaria')
                                ->maxLength(18)
                                ->required(fn (Forms\Get $get) => $get('forma_pago') === 'Transferencia')
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Máximo 18 caracteres.']),
                        ]),
                ])->columns(2),
        ];
    }

    protected static function getActaConstitutivaSchema(): array
    {
        return [
            Forms\Components\TextInput::make('notario_nombres')
                ->label('Nombre(s) del notario')
                ->maxLength(100)
                ->required()
                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 100 caracteres.']),
                
            Forms\Components\TextInput::make('notario_primer_apellido')
                ->label('Apellido Paterno')
                ->maxLength(100)
                ->required()
                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 100 caracteres.']),
                
            Forms\Components\TextInput::make('notario_segundo_apellido')
                ->label('Apellido Materno')
                ->maxLength(100)
                ->validationMessages(['max' => 'Este campo no puede tener más de 100 caracteres.']),
                
            Forms\Components\TextInput::make('numero_escritura')
                ->label('No. de escritura')
                ->maxLength(255)
                ->required()
                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 255 caracteres.']),
            
            Forms\Components\DatePicker::make('fecha_constitucion')
                ->label('Fecha de constitución')
                ->displayFormat('d/m/Y')
                ->format('Y-m-d')
                ->required()
                ->native(false)
                ->validationMessages(['required' => 'Este campo es obligatorio.']),

            Forms\Components\TextInput::make('notario_numero')
                ->label('Notario número')
                ->maxLength(255)
                ->required()
                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 255 caracteres.']),
                
            Forms\Components\TextInput::make('ciudad_registro')
                ->label('Ciudad de registro')
                ->maxLength(60) 
                ->required()
                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 60 caracteres.']),
                
            Forms\Components\Select::make('estado_registro')
                ->label('Estado de registro')
                ->options(\App\Helpers\EstadosMexico::getEstados())
                ->required()
                ->searchable()
                ->validationMessages(['required' => 'Este campo es obligatorio.']),
                
            Forms\Components\TextInput::make('numero_registro_inscripcion')
                ->label('Número de registro o inscripción')
                ->maxLength(40) 
                ->required()
                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 40 caracteres.']),
                
            Forms\Components\TextInput::make('giro_comercial')
                ->label('Giro comercial')
                ->required()
                ->validationMessages(['required' => 'Este campo es obligatorio.']),
        ];
    }

    protected static function getApoderadoSchema(): array
    {
        return [
            // SECCIÓN 1: DATOS PERSONALES DEL APODERADO
            Forms\Components\Section::make('Apoderado legal y/o representante')
                ->description('Información personal del representante')
                ->collapsible()
                ->schema([
                    Forms\Components\TextInput::make('apoderado_nombres')
                        ->label('Nombre(s)')
                        ->maxLength(255)
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 255 caracteres.']),

                    Forms\Components\TextInput::make('apoderado_primer_apellido')
                        ->label('Apellido Paterno')
                        ->maxLength(100)
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 100 caracteres.']),

                    Forms\Components\TextInput::make('apoderado_segundo_apellido')
                        ->label('Apellido Materno')
                        ->maxLength(100)
                        ->validationMessages(['max' => 'Este campo no puede tener más de 100 caracteres.']),

                    Forms\Components\Select::make('apoderado_sexo')
                        ->label('Sexo')
                        ->options([
                            'Masculino' => 'Masculino',
                            'Femenino' => 'Femenino',
                        ])
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\TextInput::make('apoderado_curp')
                        ->label('CURP')
                        ->maxLength(18)
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 18 caracteres.']),

                    Forms\Components\TextInput::make('apoderado_email')
                        ->label('Correo Electrónico')
                        ->email()
                        ->maxLength(255)
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'email' => 'Formato no válido.', 'max' => 'Este campo no puede tener más de 255 caracteres.']),

                    Forms\Components\TextInput::make('apoderado_telefono')
                        ->label('Teléfono')
                        ->tel()
                        ->length(10)
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'length' => 'Este campo debe tener 10 dígitos.']),
                ])->columns(2),

            // SECCIÓN 2: DOMICILIO DEL APODERADO
            Forms\Components\Section::make('Domicilio del Apoderado')
                ->collapsible()
                ->schema([
                    Forms\Components\TextInput::make('apoderado_calle')
                        ->label('Calle')
                        ->maxLength(200)
                        ->required()
                        ->columnSpanFull()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 200 caracteres.']),

                    Forms\Components\TextInput::make('apoderado_numero_exterior')
                        ->label('Número exterior')
                        ->maxLength(100)
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 100 caracteres.']),

                    Forms\Components\TextInput::make('apoderado_numero_interior')
                        ->label('Número interior')
                        ->maxLength(100)
                        ->validationMessages(['max' => 'Este campo no puede tener más de 100 caracteres.']),

                    Forms\Components\TextInput::make('apoderado_cp')
                        ->label('Código Postal')
                        ->length(5)
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'length' => 'Este campo debe tener 5 dígitos.']),

                    Forms\Components\TextInput::make('apoderado_colonia')
                        ->label('Colonia')
                        ->maxLength(100)
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 100 caracteres.']),

                    Forms\Components\TextInput::make('apoderado_municipio')
                        ->label('Municipio')
                        ->maxLength(100)
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 100 caracteres.']),

                    Forms\Components\Select::make('apoderado_estado')
                        ->label('Estado')
                        ->options(\App\Helpers\EstadosMexico::getEstados())
                        ->required()
                        ->searchable()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),
                ])->columns(2),

            // SECCIÓN 3: FACULTADES LEGALES
            Forms\Components\Section::make('Facultades Legales')
                ->collapsible()
                ->schema([
                    Forms\Components\Radio::make('facultades_en_acta')
                        ->label('¿Sus facultades constan en el acta constitutiva de la empresa?')
                        ->options([
                            'Si' => 'Sí',
                            'No' => 'No',
                        ])
                        ->required()
                        ->live()
                        ->columnSpanFull()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),
                
            // AVISO LEGAL 
            Forms\Components\Placeholder::make('nota_facultades')
                ->label('')
                ->content(new \Illuminate\Support\HtmlString('<p class="text-sm text-gray-500 italic"><span class="font-semibold text-warning-600">Nota Legal:</span> Deberá contar con facultades para obligarse a nombre de la sociedad ante terceros o para firmar contratos de arrendamiento y con facultades para otorgar y suscribir títulos de crédito.</p>'))
                ->columnSpanFull(),

                    // Grupo condicional: Solo si las facultades NO constan en el acta principal
                    Forms\Components\Group::make()
                        ->schema([
                            Forms\Components\TextInput::make('escritura_publica_numero')
                                ->label('Escritura Pública o Acta Número')
                                ->maxLength(12)
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 12 caracteres.']),

                            Forms\Components\TextInput::make('notario_numero_facultades')
                                ->label('Notario Número')
                                ->maxLength(100)
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 100 caracteres.']),

                            Forms\Components\DatePicker::make('fecha_escritura_facultades')
                                ->label('Fecha de Escritura o Acta')
                                ->displayFormat('d/m/Y')
                                ->format('Y-m-d')
                                ->required()
                                ->native(false)
                                ->validationMessages(['required' => 'Este campo es obligatorio.']),

                            Forms\Components\TextInput::make('numero_inscripcion_registro_publico')
                                ->label('No. de Inscripción en el Registro Público')
                                ->maxLength(50)
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 50 caracteres.']),

                            Forms\Components\TextInput::make('ciudad_registro_facultades')
                                ->label('Ciudad de Registro')
                                ->maxLength(100)
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 100 caracteres.']),

                            Forms\Components\Select::make('estado_registro_facultades')
                                ->label('Estado de Registro')
                                ->options(\App\Helpers\EstadosMexico::getEstados())
                                ->required()
                                ->searchable()
                                ->validationMessages(['required' => 'Este campo es obligatorio.']),

                            Forms\Components\Select::make('tipo_representacion_moral')
                                ->label('Tipo de Representación')
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

                            Forms\Components\TextInput::make('tipo_representacion_otro')
                                ->label('Llenar en caso de otro')
                                ->maxLength(100)
                                ->required(fn (Forms\Get $get) => $get('tipo_representacion_moral') === 'Otro')
                                ->visible(fn (Forms\Get $get) => $get('tipo_representacion_moral') === 'Otro')
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 100 caracteres.']),
                        ])
                        ->columns(2)
                        ->visible(fn (Forms\Get $get) => $get('facultades_en_acta') === 'No')
                        ->columnSpanFull(),
                ]),
        ];
    }

    protected static function getDatosInmuebleSchema(): array
    {
        return [
            Forms\Components\Select::make('tipo_inmueble')
                ->label('Tipo de inmueble')
                ->options([
                    'Casa' => 'Casa',
                    'Departamento' => 'Departamento',
                    'Local comercial' => 'Local comercial',
                    'Oficina' => 'Oficina',
                    'Bodega' => 'Bodega',
                    'Nave industrial' => 'Nave industrial',
                    'Consultorio' => 'Consultorio',
                    'Terreno' => 'Terreno',
                ])
                ->required()
                ->validationMessages(['required' => 'Este campo es obligatorio.']),

            Forms\Components\Select::make('uso_suelo')
                ->label('Uso de suelo')
                ->options([
                    'Habitacional' => 'Habitacional',
                    'Comercial' => 'Comercial',
                    'Industrial' => 'Industrial',
                ])
                ->required()
                ->validationMessages(['required' => 'Este campo es obligatorio.']),

            Forms\Components\Select::make('mascotas')
                ->label('¿Mascotas?')
                ->options([
                    'si' => 'Sí',
                    'no' => 'No',
                ])
                ->required()
                ->live()
                ->validationMessages(['required' => 'Este campo es obligatorio.']),

            Forms\Components\TextInput::make('mascotas_especifica')
                ->label('Especifique')
                ->maxLength(255)
                ->required(fn (Forms\Get $get) => $get('mascotas') === 'si')
                ->visible(fn (Forms\Get $get) => $get('mascotas') === 'si')
                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Máximo 255 caracteres.']),

            Forms\Components\TextInput::make('precio_renta')
                ->label('Precio de renta')
                ->numeric()
                ->minValue(0)
                ->prefix('$')
                ->required()
                ->validationMessages(['required' => 'Este campo es obligatorio.', 'min' => 'No puede ser negativo.']),

            Forms\Components\Select::make('iva_renta')
                ->label('IVA en la renta')
                ->options([
                    'IVA incluido' => 'IVA incluido',
                    'Mas IVA' => 'Más IVA',
                    'Sin IVA' => 'Sin IVA',
                ])
                ->required()
                ->validationMessages(['required' => 'Este campo es obligatorio.']),

            Forms\Components\Select::make('frecuencia_pago')
                ->label('Frecuencia del pago de renta')
                ->options([
                    'Mensual' => 'Mensual',
                    'Semanal' => 'Semanal',
                    'Quincenal' => 'Quincenal',
                    'Semestral' => 'Semestral',
                    'Anual' => 'Anual',
                    'Otra' => 'Otra',
                ])
                ->required()
                ->live()
                ->validationMessages(['required' => 'Este campo es obligatorio.']),

            Forms\Components\TextInput::make('frecuencia_pago_otra')
                ->label('En caso de otra frecuencia')
                ->maxLength(100)
                ->required(fn (Forms\Get $get) => $get('frecuencia_pago') === 'Otra')
                ->visible(fn (Forms\Get $get) => $get('frecuencia_pago') === 'Otra')
                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Máximo 100 caracteres.']),

            Forms\Components\Textarea::make('condiciones_pago')
                ->label('Condiciones de pago')
                ->rows(3)
                ->required()
                ->columnSpanFull()
                ->validationMessages(['required' => 'Este campo es obligatorio.']),

            Forms\Components\TextInput::make('deposito_garantia')
                ->label('Cantidad de depósito en garantía')
                ->numeric()
                ->minValue(0)
                ->prefix('$')
                ->required()
                ->validationMessages(['required' => 'Este campo es obligatorio.', 'min' => 'No puede ser negativo.']),

            Forms\Components\Select::make('paga_mantenimiento')
                ->label('¿Se paga mantenimiento?')
                ->options([
                    'si' => 'Sí',
                    'no' => 'No',
                ])
                ->required()
                ->live()
                ->validationMessages(['required' => 'Este campo es obligatorio.']),

            Forms\Components\Select::make('quien_paga_mantenimiento')
                ->label('¿Quién paga el mantenimiento?')
                ->options([
                    'Arrendatario' => 'Arrendatario',
                    'Arrendador' => 'Arrendador',
                ])
                ->required(fn (Forms\Get $get) => $get('paga_mantenimiento') === 'si')
                ->visible(fn (Forms\Get $get) => $get('paga_mantenimiento') === 'si')
                ->validationMessages(['required' => 'Este campo es obligatorio.']),

            Forms\Components\Select::make('mantenimiento_incluido_renta')
                ->label('¿Está incluido en la renta?')
                ->options([
                    'si' => 'Sí',
                    'no' => 'No',
                ])
                ->required(fn (Forms\Get $get) => $get('paga_mantenimiento') === 'si')
                ->visible(fn (Forms\Get $get) => $get('paga_mantenimiento') === 'si')
                ->validationMessages(['required' => 'Este campo es obligatorio.']),

            Forms\Components\TextInput::make('costo_mantenimiento_mensual')
                ->label('Costo mensual del mantenimiento')
                ->numeric()
                ->minValue(0)
                ->prefix('$')
                ->required(fn (Forms\Get $get) => $get('paga_mantenimiento') === 'si')
                ->visible(fn (Forms\Get $get) => $get('paga_mantenimiento') === 'si')
                ->validationMessages(['required' => 'Este campo es obligatorio.', 'min' => 'No puede ser negativo.']),

            Forms\Components\Textarea::make('instrucciones_pago')
                ->label('Instrucciones de pago')
                ->rows(3)
                ->required()
                ->columnSpanFull()
                ->validationMessages(['required' => 'Este campo es obligatorio.']),

            Forms\Components\Select::make('requiere_seguro')
                ->label('¿Se requiere contratar seguro?')
                ->options([
                    'si' => 'Sí',
                    'no' => 'No',
                ])
                ->required()
                ->live()
                ->validationMessages(['required' => 'Este campo es obligatorio.']),

            Forms\Components\TextInput::make('cobertura_seguro')
                ->label('¿Qué cobertura tiene?')
                ->maxLength(255)
                ->required(fn (Forms\Get $get) => $get('requiere_seguro') === 'si')
                ->visible(fn (Forms\Get $get) => $get('requiere_seguro') === 'si')
                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Máximo 255 caracteres.']),

            Forms\Components\TextInput::make('monto_cobertura_seguro')
                ->label('Monto que cubre el seguro')
                ->numeric()
                ->minValue(0)
                ->prefix('$')
                ->required(fn (Forms\Get $get) => $get('requiere_seguro') === 'si')
                ->visible(fn (Forms\Get $get) => $get('requiere_seguro') === 'si')
                ->validationMessages(['required' => 'Este campo es obligatorio.', 'min' => 'No puede ser negativo.']),

            Forms\Components\Textarea::make('servicios_pagar')
                ->label('Servicios que se deberán pagar del inmueble')
                ->rows(3)
                ->required()
                ->columnSpanFull()
                ->validationMessages(['required' => 'Este campo es obligatorio.']),
        ];
    }

    protected static function getDireccionInmuebleSchema(): array
    {
        return [
            Forms\Components\TextInput::make('inmueble_calle')
                ->label('Calle')
                ->maxLength(255)
                ->required()
                ->columnSpanFull()
                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 255 caracteres.']),

            Forms\Components\TextInput::make('inmueble_numero_exterior')
                ->label('Número exterior')
                ->maxLength(100)
                ->required()
                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 100 caracteres.']),

            Forms\Components\TextInput::make('inmueble_numero_interior')
                ->label('Número interior')
                ->maxLength(100)
                ->validationMessages(['max' => 'Este campo no puede tener más de 100 caracteres.']),

            Forms\Components\TextInput::make('inmueble_codigo_postal')
                ->label('Código postal')
                ->length(5)
                ->required()
                ->validationMessages(['required' => 'Este campo es obligatorio.', 'length' => 'Este campo debe tener 5 dígitos.']),

            Forms\Components\TextInput::make('inmueble_colonia')
                ->label('Colonia')
                ->maxLength(255)
                ->required()
                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 255 caracteres.']),

            Forms\Components\TextInput::make('inmueble_delegacion_municipio')
                ->label('Delegación / Municipio')
                ->maxLength(255)
                ->required()
                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 255 caracteres.']),

            Forms\Components\Select::make('inmueble_estado')
                ->label('Estado')
                ->options(\App\Helpers\EstadosMexico::getEstados())
                ->required()
                ->searchable()
                ->validationMessages(['required' => 'Este campo es obligatorio.']),

            Forms\Components\Textarea::make('inmueble_referencias')
                ->label('Referencias para ubicar el domicilio')
                ->rows(3)
                ->required()
                ->columnSpanFull()
                ->validationMessages(['required' => 'Este campo es obligatorio.']),

            Forms\Components\Textarea::make('inmueble_inventario')
                ->label('Inventario del inmueble')
                ->rows(3)
                ->required()
                ->columnSpanFull()
                ->helperText('Describa el inventario con el que cuenta el inmueble, por ejemplo, cortinas, muebles, hidroneumático, etc.')
                ->validationMessages(['required' => 'Este campo es obligatorio.']),
        ];
    }

    protected static function getRepresentacionSchema(): array
    {
        return [
            // SECCIÓN 1: DECISIÓN DE REPRESENTACIÓN
            Forms\Components\Section::make('Representación Legal')
                ->description('Defina si el propietario será representado por un tercero para firmar el contrato')
                ->schema([
                    Forms\Components\Select::make('sera_representado')
                        ->label('¿El propietario será representado por un tercero?')
                        ->options([
                            'No' => 'No',
                            'Si' => 'Sí',
                        ])
                        ->required()
                        ->live()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\Select::make('tipo_representacion')
                        ->label('Tipo de representación')
                        ->options([
                            'Autorizacion para rentar' => 'Autorización para rentar',
                            'Mandato simple (carta poder)' => 'Mandato simple (carta poder)',
                            'Carta poder ratificada ante notario' => 'Carta poder ratificada ante notario',
                            'Poder notarial' => 'Poder notarial',
                        ])
                        ->required(fn (Forms\Get $get) => $get('sera_representado') === 'Si')
                        ->visible(fn (Forms\Get $get) => $get('sera_representado') === 'Si')
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),
                ])->columns(2),

            // SECCIÓN 2: INFORMACIÓN DEL REPRESENTANTE
            Forms\Components\Section::make(' Información personal del representante')
                ->visible(fn (Forms\Get $get) => $get('sera_representado') === 'Si')
                ->collapsible()
                ->schema([
                    Forms\Components\TextInput::make('representante_nombres')
                        ->label('Nombre(s) del representante')
                        ->maxLength(255)
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 255 caracteres.']),

                    Forms\Components\TextInput::make('representante_primer_apellido')
                        ->label('Apellido Paterno')
                        ->maxLength(100)
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 100 caracteres.']),

                    Forms\Components\TextInput::make('representante_segundo_apellido')
                        ->label('Apellido Materno')
                        ->maxLength(100)
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 100 caracteres.']),

                    Forms\Components\Select::make('representante_sexo')
                        ->label('Sexo')
                        ->options([
                            'Masculino' => 'Masculino',
                            'Femenino' => 'Femenino',
                        ])
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\TextInput::make('representante_curp')
                        ->label('CURP')
                        ->maxLength(18)
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 18 caracteres.']),

                    Forms\Components\Select::make('representante_tipo_identificacion')
                        ->label('Identificación')
                        ->options([
                            'INE' => 'INE',
                            'Pasaporte' => 'Pasaporte',
                        ])
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\TextInput::make('representante_rfc')
                        ->label('RFC')
                        ->maxLength(13)
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 13 caracteres.']),

                    Forms\Components\TextInput::make('representante_telefono')
                        ->label('Teléfono')
                        ->tel()
                        ->length(10)
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'length' => 'Este campo debe tener 10 dígitos.']),

                    Forms\Components\TextInput::make('representante_email')
                        ->label('Correo electrónico')
                        ->email()
                        ->maxLength(255)
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'email' => 'Formato no válido.', 'max' => 'Este campo no puede tener más de 255 caracteres.']),
                ])->columns(2),

            // SECCIÓN 3: DOMICILIO DEL REPRESENTANTE
            Forms\Components\Section::make('Domicilio actual del representante')
                ->visible(fn (Forms\Get $get) => $get('sera_representado') === 'Si')
                ->collapsible()
                ->schema([
                    Forms\Components\TextInput::make('representante_calle')
                        ->label('Calle')
                        ->maxLength(255)
                        ->required()
                        ->columnSpanFull()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 255 caracteres.']),

                    Forms\Components\TextInput::make('representante_numero_exterior')
                        ->label('Número exterior')
                        ->maxLength(100)
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 100 caracteres.']),

                    Forms\Components\TextInput::make('representante_numero_interior')
                        ->label('Número interior')
                        ->maxLength(100)
                        ->validationMessages(['max' => 'Este campo no puede tener más de 100 caracteres.']),

                    Forms\Components\TextInput::make('representante_codigo_postal')
                        ->label('Código postal')
                        ->length(5)
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'length' => 'Este campo debe tener 5 dígitos.']),

                    Forms\Components\TextInput::make('representante_colonia')
                        ->label('Colonia')
                        ->maxLength(255)
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 255 caracteres.']),

                    Forms\Components\TextInput::make('representante_delegacion_municipio')
                        ->label('Delegación / Municipio')
                        ->maxLength(255)
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 255 caracteres.']),

                    Forms\Components\Select::make('representante_estado')
                        ->label('Estado')
                        ->options(\App\Helpers\EstadosMexico::getEstados())
                        ->required()
                        ->searchable()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\Textarea::make('representante_referencias')
                        ->label('Referencias para ubicar el domicilio')
                        ->rows(3)
                        ->required()
                        ->columnSpanFull()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),
                ])->columns(2),
        ];
    }

    protected static function getLinkedPropertySchema(): array
    {
        return [
            Forms\Components\Select::make('selected_property_id')
                ->label('Propiedad para esta renta')
                ->dehydrated(false)
                ->placeholder('Sin propiedad vinculada')
                ->live()
                ->helperText(function (?OwnerRequest $record): string {
                    if (! $record?->owner?->user_id) {
                        return 'Este propietario aún no tiene un usuario portal (`owner.user_id`). Primero vincula/crea el usuario para poder seleccionar o crear propiedad.';
                    }

                    return 'Se guardará en la renta asociada a esta solicitud; no sobrescribe los datos del inmueble capturados aquí.';
                })
                ->disabled(fn (?OwnerRequest $record): bool => ! (bool) $record?->owner?->user_id)
                ->options(function (?OwnerRequest $record): array {
                    $ownerUserId = $record?->owner?->user_id;

                    if (! $ownerUserId) {
                        return [];
                    }

                    return Property::query()
                        ->where('user_id', $ownerUserId)
                        ->orderByDesc('id')
                        ->limit(150)
                        ->get()
                        ->mapWithKeys(fn (Property $property): array => [
                            $property->id => static::propertyOptionLabel($property),
                        ])
                        ->all();
                })
                ->afterStateUpdated(function (Forms\Set $set, $state, ?OwnerRequest $record): void {
                    if (blank($state)) {
                        return;
                    }

                    $ownerUserId = $record?->owner?->user_id;
                    if (! $ownerUserId) {
                        return;
                    }

                    $property = Property::query()
                        ->where('id', $state)
                        ->where('user_id', $ownerUserId)
                        ->first();

                    if (! $property) {
                        return;
                    }

                    // Rellenamos la solicitud con datos del inmueble seleccionado para evitar recaptura.
                    $set('tipo_inmueble', $property->tipo_inmueble);
                    $set('uso_suelo', $property->uso_suelo);
                    $set('mascotas', $property->mascotas);
                    $set('mascotas_especifica', $property->mascotas_especifica);
                    $set('precio_renta', $property->precio_renta);
                    $set('iva_renta', $property->iva_renta);
                    $set('frecuencia_pago', $property->frecuencia_pago);
                    $set('frecuencia_pago_otra', $property->frecuencia_pago_otra);
                    $set('condiciones_pago', $property->condiciones_pago);
                    $set('deposito_garantia', $property->deposito_garantia);
                    $set('paga_mantenimiento', $property->paga_mantenimiento);
                    $set('quien_paga_mantenimiento', $property->quien_paga_mantenimiento);
                    $set('mantenimiento_incluido_renta', $property->mantenimiento_incluido_renta);
                    $set('costo_mantenimiento_mensual', $property->costo_mantenimiento_mensual);
                    $set('instrucciones_pago', $property->instrucciones_pago);
                    $set('requiere_seguro', $property->requiere_seguro);
                    $set('cobertura_seguro', $property->cobertura_seguro);
                    $set('monto_cobertura_seguro', $property->monto_cobertura_seguro);
                    $set('servicios_pagar', $property->servicios_pagar);
                    $set('inmueble_calle', $property->calle);
                    $set('inmueble_numero_exterior', $property->numero_exterior);
                    $set('inmueble_numero_interior', $property->numero_interior);
                    $set('inmueble_codigo_postal', $property->codigo_postal);
                    $set('inmueble_colonia', $property->colonia);
                    $set('inmueble_delegacion_municipio', $property->delegacion_municipio);
                    $set('inmueble_estado', $property->estado);
                    $set('inmueble_referencias', $property->referencias_ubicacion);
                    $set('inmueble_inventario', $property->inventario);
                })
                ->searchable()
                ->getSearchResultsUsing(function (string $search, ?OwnerRequest $record): array {
                    $ownerUserId = $record?->owner?->user_id;

                    if (! $ownerUserId) {
                        return [];
                    }

                    $term = '%' . addcslashes($search, '%_\\') . '%';

                    return Property::query()
                        ->where('user_id', $ownerUserId)
                        ->where(function ($query) use ($term): void {
                            $query->where('folio', 'like', $term)
                                ->orWhere('tipo_inmueble', 'like', $term)
                                ->orWhere('calle', 'like', $term)
                                ->orWhere('colonia', 'like', $term)
                                ->orWhere('delegacion_municipio', 'like', $term);
                        })
                        ->orderByDesc('id')
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(fn (Property $property): array => [
                            $property->id => static::propertyOptionLabel($property),
                        ])
                        ->all();
                })
                ->getOptionLabelUsing(function ($value): ?string {
                    if (blank($value)) {
                        return null;
                    }

                    $property = Property::query()->find($value);

                    return $property ? static::propertyOptionLabel($property) : null;
                })
                ->createOptionForm([
                    Forms\Components\Select::make('tipo_inmueble')
                        ->label('Tipo de inmueble')
                        ->options([
                            'Casa' => 'Casa',
                            'Departamento' => 'Departamento',
                            'Local comercial' => 'Local comercial',
                            'Oficina' => 'Oficina',
                            'Bodega' => 'Bodega',
                            'Nave industrial' => 'Nave industrial',
                            'Consultorio' => 'Consultorio',
                            'Terreno' => 'Terreno',
                        ])
                        ->required(),
                    Forms\Components\TextInput::make('precio_renta')
                        ->label('Precio de renta')
                        ->numeric()
                        ->prefix('$')
                        ->required(),
                    Forms\Components\TextInput::make('calle')
                        ->label('Calle')
                        ->required(),
                    Forms\Components\TextInput::make('numero_exterior')
                        ->label('Número exterior')
                        ->required(),
                    Forms\Components\TextInput::make('codigo_postal')
                        ->label('Código postal')
                        ->maxLength(5)
                        ->required(),
                    Forms\Components\TextInput::make('colonia')
                        ->label('Colonia')
                        ->required(),
                    Forms\Components\TextInput::make('delegacion_municipio')
                        ->label('Delegación / Municipio')
                        ->required(),
                    Forms\Components\Select::make('estado')
                        ->label('Estado')
                        ->options(\App\Helpers\EstadosMexico::getEstados())
                        ->searchable()
                        ->required(),
                ])
                ->createOptionUsing(function (array $data, ?OwnerRequest $record): int {
                    $ownerUserId = $record?->owner?->user_id;

                    if (! $ownerUserId) {
                        throw new \RuntimeException('No se puede crear propiedad sin owner.user_id.');
                    }

                    return Property::query()->create([
                        'user_id' => $ownerUserId,
                        'estatus' => 'disponible',
                        'tipo_inmueble' => $data['tipo_inmueble'] ?? null,
                        'precio_renta' => $data['precio_renta'] ?? null,
                        'calle' => $data['calle'] ?? null,
                        'numero_exterior' => $data['numero_exterior'] ?? null,
                        'codigo_postal' => $data['codigo_postal'] ?? null,
                        'colonia' => $data['colonia'] ?? null,
                        'delegacion_municipio' => $data['delegacion_municipio'] ?? null,
                        'estado' => $data['estado'] ?? null,
                    ])->id;
                }),
        ];
    }

    protected static function propertyOptionLabel(Property $property): string
    {
        $tipo = $property->tipo_inmueble ?: 'Sin tipo';
        $direccion = trim(implode(' ', array_filter([
            $property->calle,
            $property->numero_exterior,
            $property->colonia,
            $property->delegacion_municipio,
        ])));

        return "{$property->folio} | {$tipo}" . ($direccion !== '' ? " | {$direccion}" : '');
    }

    protected static function getPropertyImagesSchema(): array
    {
        return [
            Forms\Components\Placeholder::make('selected_property_images')
                ->label('')
                ->content(function (Forms\Get $get, $livewire) {
                    $propertyId = $get('selected_property_id');

                    if (! $propertyId) {
                        return new \Illuminate\Support\HtmlString('<div class="text-center text-gray-500 py-4">Selecciona una propiedad para ver sus imágenes.</div>');
                    }

                    $property = Property::query()->with('images')->find($propertyId);
                    if (! $property) {
                        return new \Illuminate\Support\HtmlString('<div class="text-center text-danger-600 py-4">Propiedad no encontrada.</div>');
                    }

                    $images = $property->images()->orderByDesc('is_portada')->orderBy('order')->get();
                    if ($images->isEmpty()) {
                        return new \Illuminate\Support\HtmlString('<div class="text-center text-gray-500 py-4">No hay imágenes cargadas para esta propiedad.</div>');
                    }

                    $html = '<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">';
                    foreach ($images as $image) {
                        $url = Storage::disk('public')->url($image->path_file);
                        $isPortada = $image->is_portada;
                        $borderColor = $isPortada ? 'border-success-500 ring-2 ring-success-500' : 'border-gray-200 dark:border-gray-700';
                        $badge = $isPortada ? '<div class="absolute top-2 right-2 bg-success-500 text-white text-xs font-bold px-2 py-1 rounded shadow-sm">Portada</div>' : '';
                        $btnPortada = $isPortada
                            ? '<button disabled class="w-full text-xs py-1.5 rounded bg-gray-100 text-gray-400 cursor-not-allowed font-medium dark:bg-gray-700">Es Portada</button>'
                            : '<button wire:click="setOwnerRequestPropertyPortada(' . $image->id . ')" class="w-full text-xs py-1.5 rounded bg-primary-600 text-white hover:bg-primary-500 transition font-medium">★ Hacer Portada</button>';
                        $btnEliminar = '<button wire:click="deleteOwnerRequestPropertyImage(' . $image->id . ')" class="text-danger-500 hover:text-danger-600 p-1" title="Eliminar">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                            </button>';

                        $html .= "
                            <div class='group relative bg-white dark:bg-gray-900 rounded-xl border {$borderColor} shadow-sm overflow-hidden flex flex-col transition hover:shadow-md'>
                                <div class='relative h-40 bg-gray-100'>
                                    <img src='{$url}' class='w-full h-full object-cover' alt='Propiedad'>
                                    {$badge}
                                    <div class='absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition flex items-center justify-center gap-2'>
                                        <a href='{$url}' target='_blank' class='bg-white/90 text-gray-800 p-1.5 rounded-full hover:bg-white' title='Ver'><svg class='w-4 h-4' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M15 12a3 3 0 11-6 0 3 3 0 016 0z'></path><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z'></path></svg></a>
                                    </div>
                                </div>
                                <div class='p-3 flex flex-col gap-2'>
                                    <div class='flex justify-between items-center'>
                                        <span class='text-xs text-gray-500'>ID: {$image->id}</span>
                                        {$btnEliminar}
                                    </div>
                                    {$btnPortada}
                                </div>
                            </div>";
                    }
                    $html .= '</div>';

                    return new \Illuminate\Support\HtmlString($html);
                })
                ->columnSpanFull(),

            Forms\Components\Actions::make([
                Forms\Components\Actions\Action::make('subir_imagen_propiedad_vinculada')
                    ->label('Subir Imagen')
                    ->color('primary')
                    ->icon('heroicon-o-photo')
                    ->form([
                        Forms\Components\FileUpload::make('file')
                            ->label('Imagen')
                            ->directory('property-images')
                            ->acceptedFileTypes(['image/*'])
                            ->maxSize(5120)
                            ->image()
                            ->required(),
                    ])
                    ->action(function (array $data, Forms\Get $get) {
                        $propertyId = $get('selected_property_id');
                        if (! $propertyId) {
                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->title('Selecciona una propiedad')
                                ->send();

                            return;
                        }

                        $property = Property::query()->find($propertyId);
                        if (! $property) {
                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->title('Propiedad no encontrada')
                                ->send();

                            return;
                        }

                        PropertyImage::query()->create([
                            'property_id' => $property->id,
                            'user_id' => auth()->id(),
                            'user_name' => auth()->user()->name,
                            'path_file' => $data['file'],
                            'is_portada' => $property->images()->count() === 0,
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Imagen subida correctamente')
                            ->send();
                    }),
            ]),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('owner.nombre_completo')
                    ->label('Propietario')
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
            'index' => Pages\ListOwnerRequests::route('/'),
            'edit' => Pages\EditOwnerRequest::route('/{record}/edit'),
        ];
    }
}