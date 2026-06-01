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
                                ->live(), 
                        ])->columns(2),

                    // PASOS EXCLUSIVOS PARA PERSONA FÍSICA

                    Forms\Components\Wizard\Step::make('Datos Personales')
                        ->description('Información personal acerca del inquilino')
                        ->icon('heroicon-o-user')
                        ->schema(self::getDatosPersonalesSchema())
                        ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                        ->columns(2),

                    Forms\Components\Wizard\Step::make('Datos de Empleo e Ingresos')
                        ->description('Información sobre el empleo y situación económica')
                        ->icon('heroicon-o-briefcase')
                        ->schema(self::getDatosEmpleoSchema())
                        ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                        ->columns(2),

                    Forms\Components\Wizard\Step::make('Uso de Propiedad')
                        ->description('Información sobre el uso que dará al inmueble')
                        ->icon('heroicon-o-home')
                        ->schema(self::getUsoPropiedadSchema())
                        ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                        ->columns(2),

                    Forms\Components\Wizard\Step::make('Referencias')
                        ->description('Referencias personales y familiares')
                        ->icon('heroicon-o-users')
                        ->schema(self::getReferenciasPersonalesSchema())
                        ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                        ->columns(2),

                    // PASOS EXCLUSIVOS PARA PERSONA MORAL

                    Forms\Components\Wizard\Step::make('Datos de la Empresa')
                        ->description('Información acerca de la empresa')
                        ->icon('heroicon-o-building-office')
                        ->schema(self::getDatosEmpresaSchema())
                        ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                        ->columns(2),

                    Forms\Components\Wizard\Step::make('Datos y Uso de Propiedad')
                        ->description('Información sobre el uso comercial del inmueble')
                        ->icon('heroicon-o-home-modern')
                        ->schema(self::getUsoPropiedadMoralSchema())
                        ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                        ->columns(2),

                    Forms\Components\Wizard\Step::make('Referencias Comerciales') 
                        ->label('Referencias') 
                        ->description('Referencias comerciales')
                        ->icon('heroicon-o-phone')
                        ->schema(self::getReferenciasComercialesSchema())
                        ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                        ->columns(2),

                ])
                ->columnSpanFull()
                //->skippable() // Permite al usuario navegar entre pasos sin que le exija llenar todo
                ->submitAction(new \Illuminate\Support\HtmlString('<button type="submit" class="fi-btn fi-btn-color-primary">Guardar Solicitud</button>')),
            ]);
    }

    protected static function getDatosPersonalesSchema(): array
    {
        return [
            // ==========================================
            // SECCIÓN: INFORMACIÓN PERSONAL
            // ==========================================
            Forms\Components\Section::make('Información Personal')
                ->description('Complete sus datos personales')
                ->schema([
                    Forms\Components\TextInput::make('nombres')
                        ->label('Nombre(s)')
                        ->maxLength(255)
                        ->required()
                        ->validationMessages([
                            'required' => 'Este campo es obligatorio.',
                            'max' => 'Este campo no puede tener más de 255 caracteres.',
                        ]),
                        
                    Forms\Components\TextInput::make('primer_apellido')
                        ->label('Apellido Paterno')
                        ->maxLength(100)
                        ->required()
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

                    Forms\Components\Radio::make('nacionalidad')
                        ->label('Nacionalidad')
                        ->options(['mexicana' => 'Mexicana', 'extranjera' => 'Extranjera'])
                        ->required()
                        ->live()
                        ->validationMessages([
                            'required' => 'Este campo es obligatorio.',
                        ]),

                    // TEXTO (Para pólizas Integrales y Amplias)
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

                    // SELECT (Solo para Póliza con Seguro)
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

                    Forms\Components\Radio::make('sexo')
                        ->label('Sexo')
                        ->options(['masculino' => 'Masculino', 'femenino' => 'Femenino'])
                        ->required()
                        ->validationMessages([
                            'required' => 'Este campo es obligatorio.',
                        ]),

                    Forms\Components\Radio::make('estado_civil')
                        ->label('Estado civil')
                        ->options(['soltero' => 'Soltero', 'casado' => 'Casado'])
                        ->required()
                        ->live()
                        ->validationMessages([
                            'required' => 'Este campo es obligatorio.',
                        ]),

                    Forms\Components\TextInput::make('email')
                        ->label('E-mail')
                        ->email()
                        ->required()
                        ->validationMessages([
                            'required' => 'Este campo es obligatorio.',
                            'email' => 'Este campo no tiene un formato válido.',
                        ]),

                    Forms\Components\TextInput::make('email_confirmacion')
                        ->label('Confirmar E-mail')
                        ->email()
                        ->required()
                        ->same('email')
                        ->dehydrated(false)
                        ->afterStateHydrated(function (Forms\Components\TextInput $component, ?\Illuminate\Database\Eloquent\Model $record) {
                            if ($record && !empty($record->email)) {
                                $component->state($record->email);
                            }
                        })
                        ->validationMessages([
                            'required' => 'Este campo es obligatorio.',
                            'same' => 'Este campo no coincide con el campo E-mail.',
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
                        ->required()
                        ->validationMessages([
                            'required' => 'Este campo es obligatorio.',
                        ]),

                    Forms\Components\DatePicker::make('fecha_nacimiento')
                        ->label('Fecha de nacimiento')
                        ->displayFormat('d/m/Y')
                        ->format('Y-m-d') 
                        ->required()
                        ->native(false)
                        ->validationMessages([
                            'required' => 'Este campo es obligatorio.',
                        ]),

                    Forms\Components\TextInput::make('rfc')
                        ->label('RFC')
                        ->maxLength(13) 
                        ->required()
                        ->validationMessages([
                            'required' => 'Este campo es obligatorio.',
                        ]),
                        
                    Forms\Components\TextInput::make('curp')
                        ->label('CURP')
                        ->length(18) 
                        ->required()
                        ->validationMessages([
                            'required' => 'Este campo es obligatorio.',
                            'length' => 'Este campo debe tener 18 caracteres.',
                        ]),
                        
                    Forms\Components\TextInput::make('telefono_celular')
                        ->label('Teléfono celular')
                        ->tel()
                        ->length(10)
                        ->required()
                        ->validationMessages([
                            'required' => 'Este campo es obligatorio.',
                            'length' => 'Este campo debe tener 10 dígitos.',
                        ]),
                        
                    Forms\Components\TextInput::make('telefono_fijo')
                        ->label('Teléfono fijo')
                        ->tel()
                        ->length(10)
                        ->validationMessages([
                            'length' => 'Este campo debe tener 10 dígitos.',
                        ]),

                    // ==========================================
                    // SUB-SECCIÓN CONDICIONAL: DATOS DEL CÓNYUGE
                    // ==========================================
                    Forms\Components\Fieldset::make('Datos del Cónyuge')
                        ->schema([
                            Forms\Components\TextInput::make('conyuge_nombres')
                                ->label('Nombre(s)')
                                ->maxLength(255)
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 255 caracteres.']),
                                
                            Forms\Components\TextInput::make('conyuge_primer_apellido')
                                ->label('Apellido Paterno')
                                ->maxLength(100)
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 100 caracteres.']),
                                
                            Forms\Components\TextInput::make('conyuge_segundo_apellido')
                                ->label('Apellido Materno')
                                ->maxLength(100)
                                ->validationMessages(['max' => 'Este campo no puede tener más de 100 caracteres.']),
                                
                            Forms\Components\TextInput::make('conyuge_telefono')
                                ->label('Teléfono')
                                ->tel()
                                ->length(10)
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'length' => 'Este campo debe tener 10 dígitos.']),
                        ])
                        ->columns(2)
                        ->visible(fn (Forms\Get $get) => $get('estado_civil') === 'casado')
                        ->columnSpanFull(),
                ])->columns(2)->collapsible(),

            // ==========================================
            // SECCIÓN: DOMICILIO ACTUAL
            // ==========================================
            Forms\Components\Section::make('Domicilio Actual')
                ->description('Complete la información de su domicilio actual')
                ->schema([
                    Forms\Components\TextInput::make('calle')
                        ->label('Calle')
                        ->maxLength(255)
                        ->required()
                        ->columnSpanFull()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 255 caracteres.']),
                        
                    Forms\Components\TextInput::make('numero_exterior')
                        ->label('Número exterior')
                        ->maxLength(100)
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 100 caracteres.']),
                        
                    Forms\Components\TextInput::make('numero_interior')
                        ->label('Número interior')
                        ->maxLength(100)
                        ->validationMessages(['max' => 'Este campo no puede tener más de 100 caracteres.']),
                        
                    Forms\Components\TextInput::make('codigo_postal')
                        ->label('Código postal')
                        ->length(5)
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'length' => 'Este campo debe tener 5 dígitos.']),
                        
                    Forms\Components\TextInput::make('colonia')
                        ->label('Colonia')
                        ->maxLength(255)
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 255 caracteres.']),
                        
                    Forms\Components\TextInput::make('delegacion_municipio')
                        ->label('Delegación / Municipio')
                        ->maxLength(255)
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 255 caracteres.']),
                        
                    Forms\Components\Select::make('estado')
                        ->label('Estado')
                        ->options(\App\Helpers\EstadosMexico::getEstados())
                        ->required()
                        ->searchable()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),
                        
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
                        ->columnSpanFull()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    // PREGUNTA DOMICILIO FISCAL (SOLO VISIBLE SI LA RENTA ES COMERCIAL)
                    Forms\Components\Radio::make('mismo_domicilio_fiscal')
                        ->label('¿Este domicilio es el mismo registrado como domicilio fiscal ante el SAT?')
                        ->options(['Si' => 'Sí', 'No' => 'No'])
                        ->required(fn (?\Illuminate\Database\Eloquent\Model $record) => $record?->rent?->tipo_inmueble === 'comercial')
                        ->visible(fn (?\Illuminate\Database\Eloquent\Model $record) => $record?->rent?->tipo_inmueble === 'comercial')
                        ->live()
                        ->columnSpanFull()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    // ==========================================
                    // SUB-SECCIÓN CONDICIONAL: DOMICILIO FISCAL 
                    // (Visible si es comercial y seleccionó "No")
                    // ==========================================
                    Forms\Components\Fieldset::make('Domicilio Fiscal')
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
                        ->visible(fn (Forms\Get $get, ?\Illuminate\Database\Eloquent\Model $record) => $record?->rent?->tipo_inmueble === 'comercial' && $get('mismo_domicilio_fiscal') === 'No')
                        ->columnSpanFull(),

                        // METROS CUADRADOS (SOLO VISIBLE SI LA PÓLIZA ES CON SEGURO)
                        Forms\Components\TextInput::make('metros_cuadrados')
                        ->label('Número de m²')
                        ->helperText('Ingresa el no. de metros cuadrados aproximados del domicilio que habitas actualmente.')
                        ->numeric()
                        ->minValue(1)
                        ->required(fn (?\Illuminate\Database\Eloquent\Model $record) => 
                            $record?->rent?->tipo_poliza === 'PÓLIZA CON SEGURO'
                        )
                        ->columnSpanFull(),

                    // ==========================================
                    // SUB-SECCIÓN CONDICIONAL: ARRENDADOR ACTUAL
                    // ==========================================
                    Forms\Components\Fieldset::make('Datos del arrendador actual')
                        ->schema([
                            Forms\Components\TextInput::make('arrendador_actual_nombres')
                                ->label('Nombre(s)')
                                ->maxLength(255)
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 255 caracteres.']),
                                
                            Forms\Components\TextInput::make('arrendador_actual_primer_apellido')
                                ->label('Apellido Paterno')
                                ->maxLength(100)
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 100 caracteres.']),
                                
                            Forms\Components\TextInput::make('arrendador_actual_segundo_apellido')
                                ->label('Apellido Materno')
                                ->maxLength(100)
                                ->validationMessages(['max' => 'Este campo no puede tener más de 100 caracteres.']),
                                
                            Forms\Components\TextInput::make('arrendador_actual_telefono')
                                ->label('Teléfono')
                                ->tel()
                                ->length(10)
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'length' => 'Este campo debe tener 10 dígitos.']),
                                
                            Forms\Components\TextInput::make('renta_actual')
                                ->label('Renta que paga actualmente')
                                ->numeric()
                                ->minValue(0)
                                ->prefix('$')
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'min' => 'Este campo no puede ser un valor negativo.']),
                                
                            Forms\Components\TextInput::make('ocupa_desde_ano')
                                ->label('Ocupa el lugar desde (año)')
                                ->numeric()
                                ->minValue(1900)
                                ->maxValue(now()->year)
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.']),
                        ])
                        ->columns(2)
                        ->visible(fn (Forms\Get $get) => $get('situacion_habitacional') === 'Inquilino')
                        ->columnSpanFull(), 
                ])->columns(2)->collapsible(),
        ];
    }

    protected static function getDatosEmpleoSchema(): array
    {
        return [
            // ==========================================
            // SECCIÓN: INFORMACIÓN SOBRE EL EMPLEO
            // ==========================================
            Forms\Components\Section::make('Información sobre el empleo')
                ->description('Complete la información de su situación laboral')
                ->schema([
                    Forms\Components\TextInput::make('profesion_oficio_puesto')
                        ->label('Profesión, oficio o puesto')
                        ->maxLength(255)
                        ->required() 
                        ->validationMessages([
                            'required' => 'Este campo es obligatorio.',
                            'max' => 'Este campo no puede tener más de 255 caracteres.',
                        ]),

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

                    Forms\Components\TextInput::make('telefono_empleo')
                        ->label('Teléfono del Empleo')
                        ->tel()
                        ->length(10) 
                        ->required()
                        ->validationMessages([
                            'required' => 'Este campo es obligatorio.',
                            'length' => 'Este campo debe tener 10 dígitos.',
                        ]),

                    Forms\Components\TextInput::make('extension_empleo')
                        ->label('No. de extensión')
                        ->maxLength(255)
                        ->validationMessages(['max' => 'Este campo no puede tener más de 255 caracteres.']),

                    Forms\Components\TextInput::make('empresa_trabaja')
                        ->label('Empresa donde trabaja')
                        ->maxLength(255)
                        ->required()
                        ->columnSpanFull()
                        ->validationMessages([
                            'required' => 'Este campo es obligatorio.',
                            'max' => 'Este campo no puede tener más de 255 caracteres.',
                        ]),

                    Forms\Components\TextInput::make('calle_empleo')
                        ->label('Calle')
                        ->maxLength(255)
                        ->required()
                        ->columnSpanFull()
                        ->validationMessages([
                            'required' => 'Este campo es obligatorio.',
                            'max' => 'Este campo no puede tener más de 255 caracteres.',
                        ]),

                    Forms\Components\TextInput::make('numero_exterior_empleo')
                        ->label('Número exterior')
                        ->maxLength(255) 
                        ->required()
                        ->validationMessages([
                            'required' => 'Este campo es obligatorio.',
                            'max' => 'Este campo no puede tener más de 255 caracteres.',
                        ]),

                    Forms\Components\TextInput::make('numero_interior_empleo')
                        ->label('Número interior')
                        ->maxLength(255)
                        ->validationMessages(['max' => 'Este campo no puede tener más de 255 caracteres.']),

                    Forms\Components\TextInput::make('codigo_postal_empleo')
                        ->label('Código postal')
                        ->length(5)
                        ->required()
                        ->validationMessages([
                            'required' => 'Este campo es obligatorio.',
                            'length' => 'Este campo debe tener 5 dígitos.',
                        ]),

                    Forms\Components\TextInput::make('colonia_empleo')
                        ->label('Colonia')
                        ->maxLength(255)
                        ->required()
                        ->validationMessages([
                            'required' => 'Este campo es obligatorio.',
                            'max' => 'Este campo no puede tener más de 255 caracteres.',
                        ]),

                    Forms\Components\TextInput::make('delegacion_municipio_empleo')
                        ->label('Delegación / Municipio')
                        ->maxLength(255)
                        ->required()
                        ->validationMessages([
                            'required' => 'Este campo es obligatorio.',
                            'max' => 'Este campo no puede tener más de 255 caracteres.',
                        ]),

                    Forms\Components\Select::make('estado_empleo')
                        ->label('Estado')
                        ->options(\App\Helpers\EstadosMexico::getEstados())
                        ->required()
                        ->searchable()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),
                    
                    Forms\Components\DatePicker::make('fecha_ingreso')
                        ->label('Fecha de Ingreso')
                        ->displayFormat('d/m/Y')
                        ->format('Y-m-d')
                        ->required()
                        ->native(false)
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),
                ])->columns(2)->collapsible(),

            // ==========================================
            // SECCIÓN: JEFE INMEDIATO
            // ==========================================
            Forms\Components\Section::make('Jefe inmediato')
                ->description('Complete la información de su jefe inmediato')
                ->schema([
                    Forms\Components\TextInput::make('jefe_nombres')
                        ->label('Nombre (s)')
                        ->maxLength(255)
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 255 caracteres.']),
                        
                    Forms\Components\TextInput::make('jefe_primer_apellido')
                        ->label('Apellido Paterno')
                        ->maxLength(255)
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 255 caracteres.']),
                        
                    Forms\Components\TextInput::make('jefe_segundo_apellido')
                        ->label('Apellido Materno')
                        ->maxLength(255)
                        ->validationMessages(['max' => 'Este campo no puede tener más de 255 caracteres.']),
                        
                    Forms\Components\TextInput::make('jefe_telefono')
                        ->label('Teléfono de oficina')
                        ->tel()
                        ->length(10)
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'length' => 'Este campo debe tener 10 dígitos.']),
                        
                    Forms\Components\TextInput::make('jefe_extension')
                        ->label('Número de extensión')
                        ->maxLength(255)
                        ->validationMessages(['max' => 'Este campo no puede tener más de 255 caracteres.']),
                ])->columns(2)->collapsible(),

            // ==========================================
            // SECCIÓN: INGRESOS
            // ==========================================
            Forms\Components\Section::make('Ingresos')
                ->description('Complete la información de sus ingresos')
                ->schema([
                    Forms\Components\TextInput::make('ingreso_mensual_comprobable')
                        ->label('Ingreso mensual comprobable')
                        ->numeric()
                        ->minValue(0) 
                        ->prefix('$')
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'min' => 'Este campo no puede ser negativo.']),
                        
                    Forms\Components\TextInput::make('ingreso_mensual_no_comprobable')
                        ->label('Ingreso mensual no comprobable')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('$')
                        ->validationMessages(['min' => 'Este campo no puede ser negativo.']),
                        
                    Forms\Components\TextInput::make('numero_personas_dependen')
                        ->label('Número de personas que dependen de usted')
                        ->numeric()
                        ->minValue(0)
                        ->required()
                        ->columnSpanFull()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'min' => 'Este campo no puede ser negativo.']),
                        
                    Forms\Components\Radio::make('otra_persona_aporta')
                        ->label('¿Alguna otra persona aporta al ingreso familiar?')
                        ->options([0 => 'No', 1 => 'Sí'])
                        ->required()
                        ->live()
                        ->columnSpanFull()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    // ==========================================
                    // SUB-SECCIÓN CONDICIONAL: PERSONA QUE APORTA
                    // ==========================================
                    Forms\Components\Fieldset::make('Información de la persona que aporta al ingreso familiar')
                        ->schema([
                            Forms\Components\TextInput::make('numero_personas_aportan')
                                ->label('Número de personas que aportan al ingreso familiar')
                                ->numeric()
                                ->minValue(0) 
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'min' => 'Este campo no puede ser negativo.']),
                                
                            Forms\Components\TextInput::make('persona_aporta_nombres')
                                ->label('Nombre (s)')
                                ->maxLength(255)
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 255 caracteres.']),
                                
                            Forms\Components\TextInput::make('persona_aporta_primer_apellido')
                                ->label('Apellido Paterno')
                                ->maxLength(255)
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 255 caracteres.']),
                                
                            Forms\Components\TextInput::make('persona_aporta_segundo_apellido')
                                ->label('Apellido Materno')
                                ->maxLength(255)
                                ->validationMessages(['max' => 'Este campo no puede tener más de 255 caracteres.']),
                                
                            Forms\Components\TextInput::make('persona_aporta_parentesco')
                                ->label('Parentesco')
                                ->maxLength(255)
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 255 caracteres.']),
                                
                            Forms\Components\TextInput::make('persona_aporta_telefono')
                                ->label('Teléfono')
                                ->tel()
                                ->length(10)
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'length' => 'Este campo debe tener 10 dígitos.']),
                                
                            Forms\Components\TextInput::make('persona_aporta_empresa')
                                ->label('Empresa donde trabaja')
                                ->maxLength(255)
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 255 caracteres.']),
                                
                            Forms\Components\TextInput::make('persona_aporta_ingreso_comprobable')
                                ->label('Ingreso mensual comprobable')
                                ->numeric()
                                ->minValue(0)
                                ->prefix('$')
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'min' => 'Este campo no puede ser negativo.']),
                        ])
                        ->columns(2)
                        ->visible(fn (Forms\Get $get) => $get('otra_persona_aporta') == 1)
                        ->columnSpanFull(),
                ])->columns(2)->collapsible(),
        ];
    }

    protected static function getUsoPropiedadSchema(): array
    {
        return [
            // ==========================================
            // GRUPO 1: USO COMERCIAL
            // ==========================================
            Forms\Components\Group::make()
                ->schema([
                    Forms\Components\Section::make('Datos del uso Comercial')
                        ->description('Complete la información sobre el uso que dará al inmueble')
                        ->collapsible()
                        ->schema([
                            Forms\Components\Select::make('tipo_inmueble_desea')
                                ->label('Tipo de inmueble que desea rentar')
                                ->options(['Local' => 'Local', 'Oficina' => 'Oficina', 'Consultorio' => 'Consultorio', 'Bodega' => 'Bodega', 'Nave Industrial' => 'Nave Industrial'])
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.']),
                                
                            Forms\Components\TextInput::make('giro_negocio')
                                ->label('¿Cuál es el giro de su negocio?')
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.']),
                                
                            Forms\Components\Textarea::make('experiencia_giro')
                                ->label('Describa brevemente su experiencia en el giro')
                                ->rows(3)
                                ->maxLength(250) 
                                ->required()
                                ->columnSpanFull()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 250 caracteres.']),
                                
                            Forms\Components\Textarea::make('propositos_arrendamiento')
                                ->label('Propósitos del arrendamiento')
                                ->rows(3)
                                ->required()
                                ->helperText('Establecer sucursal, oficina matriz, domicilio fiscal, etc.')
                                ->columnSpanFull()
                                ->validationMessages(['required' => 'Este campo es obligatorio.']),
                                
                            Forms\Components\Radio::make('sustituye_otro_domicilio')
                                ->label('¿Este inmueble sustituirá otro domicilio?')
                                ->options([0 => 'No', 1 => 'Sí'])
                                ->required()
                                ->live()
                                ->columnSpanFull()
                                ->validationMessages(['required' => 'Este campo es obligatorio.']),
                        ])->columns(2),

                    // SUB-SECCIÓN CONDICIONAL COMERCIAL: DOMICILIO ANTERIOR
                    Forms\Components\Section::make('Información del domicilio anterior')
                        ->collapsible()
                        ->visible(fn (Forms\Get $get) => $get('sustituye_otro_domicilio') == 1)
                        ->schema([
                            Forms\Components\TextInput::make('domicilio_anterior_calle')
                                ->label('Calle')
                                ->maxLength(100) 
                                ->required()
                                ->columnSpanFull()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 100 caracteres.']),
                                
                            Forms\Components\TextInput::make('domicilio_anterior_numero_exterior')
                                ->label('Núm Ext')
                                ->maxLength(10) 
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 10 caracteres.']),
                                
                            Forms\Components\TextInput::make('domicilio_anterior_numero_interior')
                                ->label('Núm Int')
                                ->maxLength(10) 
                                ->validationMessages(['max' => 'Este campo no puede tener más de 10 caracteres.']),
                                
                            Forms\Components\TextInput::make('domicilio_anterior_codigo_postal')
                                ->label('C.P.')
                                ->length(5)
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'length' => 'Este campo debe tener 5 dígitos.']),
                                
                            Forms\Components\TextInput::make('domicilio_anterior_colonia')
                                ->label('Colonia')
                                ->maxLength(100) 
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 100 caracteres.']),
                                
                            Forms\Components\TextInput::make('domicilio_anterior_delegacion_municipio')
                                ->label('Municipio')
                                ->maxLength(100) 
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 100 caracteres.']),
                                
                            Forms\Components\Select::make('domicilio_anterior_estado')
                                ->label('Estado')
                                ->options(\App\Helpers\EstadosMexico::getEstados())
                                ->required()
                                ->searchable()
                                ->validationMessages(['required' => 'Este campo es obligatorio.']),
                                
                            Forms\Components\Textarea::make('motivo_cambio_domicilio')
                                ->label('Motivo del cambio')
                                ->required()
                                ->columnSpanFull()
                                ->validationMessages(['required' => 'Este campo es obligatorio.']),
                        ])->columns(2),
                ])
                ->visible(function ($record) {
                    if (!$record || !$record->rent) return false;
                    return $record->rent->tipo_inmueble === 'comercial'; 
                })
                ->columnSpanFull(),

            // ==========================================
            // GRUPO 2: USO RESIDENCIAL 
            // ==========================================
            Forms\Components\Group::make()
                ->schema([
                    Forms\Components\Section::make('Información sobre el uso de la propiedad')
                        ->description('Información sobre los habitantes.')
                        ->collapsible()
                        ->schema([
                            Forms\Components\TextInput::make('numero_adultos')
                                ->label('Número de adultos que ocuparán el inmueble:')
                                ->numeric()
                                ->integer() 
                                ->minValue(0) 
                                ->required()
                                ->validationMessages([
                                    'required' => 'Este campo es obligatorio.', 
                                    'integer' => 'Este campo debe ser un número entero.', 
                                    'min' => 'Este campo no puede ser negativo.'
                                ]),
                                
                            Forms\Components\TextInput::make('nombre_adulto_1')
                                ->label('Nombre completo del adulto 1:')
                                ->maxLength(255)
                                ->validationMessages(['max' => 'Este campo no puede tener más de 255 caracteres.']),
                                
                            Forms\Components\TextInput::make('nombre_adulto_2')
                                ->label('Nombre completo del adulto 2:')
                                ->maxLength(255)
                                ->validationMessages(['max' => 'Este campo no puede tener más de 255 caracteres.']),
                                
                            Forms\Components\TextInput::make('nombre_adulto_3')
                                ->label('Nombre completo del adulto 3:')
                                ->maxLength(255)
                                ->validationMessages(['max' => 'Este campo no puede tener más de 255 caracteres.']),
                                
                            Forms\Components\TextInput::make('nombre_adulto_4')
                                ->label('Nombre completo del adulto 4:')
                                ->maxLength(255)
                                ->validationMessages(['max' => 'Este campo no puede tener más de 255 caracteres.']),
                                
                            Forms\Components\Radio::make('tiene_menores')
                                ->label('¿Hay menores de 18 años?')
                                ->options([0 => 'No', 1 => 'Sí'])
                                ->required()
                                ->inline()
                                ->live()
                                ->validationMessages(['required' => 'Este campo es obligatorio.']),
                                
                            Forms\Components\TextInput::make('cuantos_menores')
                                ->label('¿Cuántos?')
                                ->numeric()
                                ->integer()
                                ->minValue(0)
                                ->required(fn (Forms\Get $get) => $get('tiene_menores') == 1)
                                ->visible(fn (Forms\Get $get) => $get('tiene_menores') == 1)
                                ->validationMessages([
                                    'required' => 'Este campo es obligatorio.', 
                                    'integer' => 'Debe ser un número entero.', 
                                    'min' => 'No puede ser negativo.'
                                ]),
                        ])->columns(1),

                    Forms\Components\Section::make('Domicilio')
                        ->collapsible()
                        ->schema([
                            Forms\Components\Textarea::make('motivo_cambio_domicilio') 
                                ->label('Motivo por el cual se cambia:')
                                ->rows(3)
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.']),
                        ]),

                    Forms\Components\Section::make('Información sobre mascotas')
                        ->collapsible()
                        ->schema([
                            Forms\Components\Radio::make('tiene_mascotas')
                                ->label('¿Tiene mascotas?')
                                ->options([0 => 'No', 1 => 'Sí'])
                                ->required()
                                ->inline()
                                ->live()
                                ->validationMessages(['required' => 'Este campo es obligatorio.']),
                                
                            Forms\Components\TextInput::make('especificar_mascotas')
                                ->label('Especifique:') 
                                ->maxLength(255)
                                ->required(fn (Forms\Get $get) => $get('tiene_mascotas') == 1)
                                ->visible(fn (Forms\Get $get) => $get('tiene_mascotas') == 1)
                                ->columnSpanFull()
                                ->validationMessages([
                                    'required' => 'Este campo es obligatorio.', 
                                    'max' => 'Este campo no puede tener más de 255 caracteres.'
                                ]),
                        ]),
                ])
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
            // ==========================================
            // SECCIÓN: REFERENCIAS PERSONALES
            // ==========================================
            Forms\Components\Section::make('Información sobre referencias personales')
                ->description('Complete la información de sus referencias personales')
                ->schema([
                    
                    // REFERENCIA PERSONAL 1
                    Forms\Components\Fieldset::make('Referencia personal 1')
                        ->schema([
                            Forms\Components\TextInput::make('referencia_personal1_nombres')
                                ->label('Nombre (s)')
                                ->maxLength(255)
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 255 caracteres.']),
                                
                            Forms\Components\TextInput::make('referencia_personal1_primer_apellido')
                                ->label('Apellido Paterno')
                                ->maxLength(255)
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 255 caracteres.']),
                                
                            Forms\Components\TextInput::make('referencia_personal1_segundo_apellido')
                                ->label('Apellido Materno')
                                ->maxLength(255)
                                ->validationMessages(['max' => 'Este campo no puede tener más de 255 caracteres.']),
                                
                            Forms\Components\TextInput::make('referencia_personal1_relacion')
                                ->label('Relación')
                                ->maxLength(255)
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 255 caracteres.']),
                                
                            Forms\Components\TextInput::make('referencia_personal1_telefono')
                                ->label('Teléfono')
                                ->tel()
                                ->length(10)
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'length' => 'Este campo debe tener 10 digitos.']),
                        ])->columns(2),

                    // REFERENCIA PERSONAL 2
                    Forms\Components\Fieldset::make('Referencia personal 2')
                        ->schema([
                            Forms\Components\TextInput::make('referencia_personal2_nombres')
                                ->label('Nombre (s)')
                                ->maxLength(255)
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 255 caracteres.']),
                                
                            Forms\Components\TextInput::make('referencia_personal2_primer_apellido')
                                ->label('Apellido Paterno')
                                ->maxLength(255)
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 255 caracteres.']),
                                
                            Forms\Components\TextInput::make('referencia_personal2_segundo_apellido')
                                ->label('Apellido Materno')
                                ->maxLength(255)
                                ->validationMessages(['max' => 'Este campo no puede tener más de 255 caracteres.']),
                                
                            Forms\Components\TextInput::make('referencia_personal2_relacion')
                                ->label('Relación')
                                ->maxLength(255)
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 255 caracteres.']),
                                
                            Forms\Components\TextInput::make('referencia_personal2_telefono')
                                ->label('Teléfono')
                                ->tel()
                                ->length(10)
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'length' => 'Este campo debe tener 10 digitos.']),
                        ])->columns(2),
                ])->collapsible(),

            // ==========================================
            // SECCIÓN: REFERENCIAS FAMILIARES
            // ==========================================
            Forms\Components\Section::make('Información sobre referencias familiares')
                ->description('Complete la información de sus referencias familiares')
                ->schema([
                    
                    // FAMILIAR 1
                    Forms\Components\Fieldset::make('Referencia familiar 1')
                        ->schema([
                            Forms\Components\TextInput::make('referencia_familiar1_nombres')
                                ->label('Nombre (s)')
                                ->maxLength(255)
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 255 caracteres.']),
                                
                            Forms\Components\TextInput::make('referencia_familiar1_primer_apellido')
                                ->label('Apellido Paterno')
                                ->maxLength(255)
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 255 caracteres.']),
                                
                            Forms\Components\TextInput::make('referencia_familiar1_segundo_apellido')
                                ->label('Apellido Materno')
                                ->maxLength(255)
                                ->validationMessages(['max' => 'Este campo no puede tener más de 255 caracteres.']),
                                
                            Forms\Components\TextInput::make('referencia_familiar1_relacion')
                                ->label('Relación')
                                ->maxLength(255)
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 255 caracteres.']),
                                
                            Forms\Components\TextInput::make('referencia_familiar1_telefono')
                                ->label('Teléfono')
                                ->tel()
                                ->length(10)
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'length' => 'Este campo debe tener 10 digitos.']),
                        ])->columns(2),

                    // FAMILIAR 2
                    Forms\Components\Fieldset::make('Referencia familiar 2')
                        ->schema([
                            Forms\Components\TextInput::make('referencia_familiar2_nombres')
                                ->label('Nombre (s)')
                                ->maxLength(255)
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 255 caracteres.']),
                                
                            Forms\Components\TextInput::make('referencia_familiar2_primer_apellido')
                                ->label('Apellido Paterno')
                                ->maxLength(255)
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 255 caracteres.']),
                                
                            Forms\Components\TextInput::make('referencia_familiar2_segundo_apellido')
                                ->label('Apellido Materno')
                                ->maxLength(255)
                                ->validationMessages(['max' => 'Este campo no puede tener más de 255 caracteres.']),
                                
                            Forms\Components\TextInput::make('referencia_familiar2_relacion')
                                ->label('Relación')
                                ->maxLength(255)
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 255 caracteres.']),
                                
                            Forms\Components\TextInput::make('referencia_familiar2_telefono')
                                ->label('Teléfono')
                                ->tel()
                                ->length(10)
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'length' => 'Este campo debe tener 10 digitos.']),
                        ])->columns(2),
                ])->collapsible(),
        ];
    }

    protected static function getDatosEmpresaSchema(): array
    {
        return [
            Forms\Components\Section::make('Información acerca de la empresa')
                ->schema([
                    Forms\Components\TextInput::make('razon_social')
                        ->label('Nombre de la empresa o razón social')
                        ->maxLength(255)
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 255 caracteres.']),
                        
                    Forms\Components\TextInput::make('email')
                        ->label('Correo electrónico')
                        ->email()
                        ->maxLength(255)
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'email' => 'Este campo no tiene un formato válido.', 'max' => 'Este campo no puede tener más de 255 caracteres.']),
                        
                    Forms\Components\TextInput::make('dominio_internet')
                        ->label('Dominio de internet de la empresa')
                        ->maxLength(150) 
                        ->validationMessages(['max' => 'Este campo no puede tener más de 150 caracteres.']),
                        
                    Forms\Components\TextInput::make('rfc')
                        ->label('RFC')
                        ->maxLength(13) 
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
                        
                    Forms\Components\TextInput::make('telefono')
                        ->label('Teléfono')
                        ->tel()
                        ->length(10) 
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'length' => 'Este campo debe tener 10 dígitos.']),
                        
                    // Ingreso mensual (ya sea residencial o comercial)
                    Forms\Components\TextInput::make('ingreso_mensual_promedio')
                        ->label('Ingreso mensual promedio')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('$')
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'min' => 'Este campo no puede ser negativo.']),
                ])->columns(2)->collapsible(),

            // ==========================================
            // DOMICILIO ACTUAL DE LA EMPRESA
            // ==========================================
            Forms\Components\Section::make('Domicilio actual de la empresa')
                ->schema([
                    Forms\Components\TextInput::make('calle')
                        ->label('Calle')
                        ->maxLength(200) 
                        ->required()
                        ->columnSpanFull()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 200 caracteres.']), 
                        
                    Forms\Components\TextInput::make('numero_exterior')
                        ->label('Número exterior')
                        ->helperText('De no tener, escribir (S.N)')
                        ->maxLength(100)
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 100 caracteres.']),
                        
                    Forms\Components\TextInput::make('numero_interior')
                        ->label('Número interior')
                        ->helperText('De no tener, escribir (S.N)')
                        ->maxLength(100)
                        ->validationMessages(['max' => 'Este campo no puede tener más de 100 caracteres.']),
                        
                    Forms\Components\TextInput::make('codigo_postal')
                        ->label('CP')
                        ->length(5)
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'length' => 'Este campo debe tener 5 dígitos.']),
                        
                    Forms\Components\TextInput::make('colonia')
                        ->label('Colonia')
                        ->maxLength(100) 
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 100 caracteres.']),
                        
                    Forms\Components\TextInput::make('municipio')
                        ->label('Municipio')
                        ->maxLength(100) 
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 100 caracteres.']),
                        
                    Forms\Components\Select::make('estado')
                        ->label('Estado')
                        ->options(\App\Helpers\EstadosMexico::getEstados())
                        ->required()
                        ->searchable()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),
                        
                    Forms\Components\Textarea::make('referencias_ubicacion')
                        ->label('Referencias de la ubicación')
                        ->rows(3)
                        ->required()
                        ->columnSpanFull()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    Forms\Components\Radio::make('mismo_domicilio_fiscal')
                        ->label('¿Este domicilio es el mismo registrado como domicilio fiscal ante el SAT?')
                        ->options(['Si' => 'Sí', 'No' => 'No'])
                        ->required()
                        ->live()
                        ->columnSpanFull()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),
                ])->columns(2)->collapsible(),

            // ==========================================
            // DOMICILIO FISCAL (Condicional)
            // ==========================================
            Forms\Components\Section::make('Domicilio Fiscal')
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
                ])->columns(2)->collapsible(),

            // ==========================================
            // ACTA CONSTITUTIVA
            // ==========================================
            Forms\Components\Section::make('Datos del acta constitutiva')
                ->schema([
                    Forms\Components\TextInput::make('notario_nombres')
                        ->label('Nombres(s) del notario')
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
                        ->label('No. De escritura')
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
                        ->label('Notario numero')
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
                        ->label('Numero de registro o inscripción')
                        ->maxLength(40) 
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 40 caracteres.']),
                        
                    Forms\Components\TextInput::make('giro_comercial')
                        ->label('Giro comercial')
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),
                ])->columns(2)->collapsible(),

            // ==========================================
            // APODERADO LEGAL
            // ==========================================
            Forms\Components\Section::make('Apoderado legal y/o representante')
                ->schema([
                    Forms\Components\TextInput::make('apoderado_nombres')
                        ->label('Nombre (s)')
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
                        ->maxLength(50) 
                        ->validationMessages(['max' => 'Este campo no puede tener más de 50 caracteres.']),
                        
                    Forms\Components\Select::make('apoderado_sexo')
                        ->label('Sexo')
                        ->options(['Masculino' => 'Masculino', 'Femenino' => 'Femenino']) 
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),
                        
                    Forms\Components\TextInput::make('apoderado_telefono')
                        ->label('Telefono')
                        ->tel()
                        ->length(10) 
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'length' => 'Este campo debe tener 10 dígitos.']),
                        
                    Forms\Components\TextInput::make('apoderado_extension')
                        ->label('Extension')
                        ->maxLength(50) 
                        ->validationMessages(['max' => 'Este campo no puede tener más de 50 caracteres.']),
                        
                    Forms\Components\TextInput::make('apoderado_email')
                        ->label('Correo electrónico')
                        ->email()
                        ->maxLength(255)
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'email' => 'Este campo no tiene un formato válido.', 'max' => 'Este campo no puede tener más de 255 caracteres.']),
                        
                    Forms\Components\Radio::make('facultades_en_acta')
                        ->label('¿Sus facultades constan en el acta constitutiva de la empresa?')
                        ->options([0 => 'Sí', 1 => 'No']) 
                        ->required()
                        ->live()
                        ->columnSpanFull()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),

                    // ==========================================
                    // AVISO LEGAL 
                    // ==========================================
                    Forms\Components\Placeholder::make('nota_facultades')
                        ->label('')
                        ->content(new \Illuminate\Support\HtmlString('<p class="text-sm text-gray-500 italic"><span class="font-semibold text-warning-600">Nota Legal:</span> Deberá contar con facultades para obligarse a nombre de la sociedad ante terceros o para firmar contratos de arrendamiento y con facultades para otorgar y suscribir títulos de crédito.</p>'))
                        ->columnSpanFull(),

                    // ==========================================
                    // FACULTADES EN ACTA (Condicional)
                    // ==========================================
                    Forms\Components\Group::make()
                        ->schema([
                            Forms\Components\Placeholder::make('facultades_info')->label('Facultades en acta')->columnSpanFull(),
                            
                            Forms\Components\TextInput::make('escritura_publica_numero')
                                ->label('Escritura publica o acta numero')
                                ->maxLength(12) 
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 12 caracteres.']),
                                
                            Forms\Components\TextInput::make('notario_numero_facultades')
                                ->label('Notario numero')
                                ->maxLength(100) 
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 100 caracteres.']),
                            
                            Forms\Components\DatePicker::make('fecha_escritura_facultades')
                                ->label('Fecha de escritura o acta')
                                ->displayFormat('d/m/Y')
                                ->format('Y-m-d')
                                ->required()
                                ->native(false)
                                ->validationMessages(['required' => 'Este campo es obligatorio.']),

                            Forms\Components\TextInput::make('numero_inscripcion_registro_publico')
                                ->label('No. de inscripción en el registro publico')
                                ->maxLength(50) 
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 50 caracteres.']),
                                
                            Forms\Components\TextInput::make('ciudad_registro_facultades')
                                ->label('Ciudad de registro')
                                ->maxLength(100) 
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 100 caracteres.']),
                                
                            Forms\Components\Select::make('estado_registro_facultades')
                                ->label('Estado de registro')
                                ->options(\App\Helpers\EstadosMexico::getEstados())
                                ->required()
                                ->searchable()
                                ->validationMessages(['required' => 'Este campo es obligatorio.']),
                            
                            Forms\Components\DatePicker::make('fecha_inscripcion_facultades')
                                ->label('Fecha de inscripción')
                                ->displayFormat('d/m/Y')
                                ->format('Y-m-d')
                                ->required()
                                ->native(false)
                                ->validationMessages(['required' => 'Este campo es obligatorio.']),

                            Forms\Components\Select::make('tipo_representacion')
                                ->label('Tipo de representación')
                                ->options([
                                    'Administrador único' => 'Administrador único', 
                                    'Presidente del consejo' => 'Presidente del consejo', 
                                    'Socio administrador' => 'Socio administrador', 
                                    'Gerente' => 'Gerente', 
                                    'Otro' => 'Otro'
                                ])
                                ->required()
                                ->live()
                                ->validationMessages(['required' => 'Este campo es obligatorio.']),
                                
                            Forms\Components\TextInput::make('tipo_representacion_otro')
                                ->label('Llenar en caso de otro')
                                ->maxLength(100) 
                                ->required(fn (Forms\Get $get) => $get('tipo_representacion') === 'Otro')
                                ->visible(fn (Forms\Get $get) => $get('tipo_representacion') === 'Otro')
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 100 caracteres.']),
                        ])
                        ->columns(2)
                        ->visible(fn (Forms\Get $get) => (int)$get('facultades_en_acta') === 1)
                        ->columnSpanFull(),
                ])->columns(2)->collapsible(),
        ];
    }

    protected static function getUsoPropiedadMoralSchema(): array
    {
        return [
            // ==========================================
            // GRUPO 1: USO COMERCIAL
            // ==========================================
            Forms\Components\Group::make([
                Forms\Components\Placeholder::make('uso_comercial_info_moral')
                    ->label('DATOS DEL USO COMERCIAL')
                    ->content('Complete la información sobre el uso comercial que dará al inmueble')
                    ->columnSpanFull(),
                    
                Forms\Components\Select::make('tipo_inmueble_desea')
                    ->label('Tipo de inmueble que desea rentar')
                    ->options(['Local' => 'Local', 'Oficina' => 'Oficina', 'Consultorio' => 'Consultorio', 'Bodega' => 'Bodega', 'Nave Industrial' => 'Nave Industrial'])
                    ->required()
                    ->validationMessages(['required' => 'Este campo es obligatorio.']),
                    
                Forms\Components\TextInput::make('giro_negocio')
                    ->label('¿Cuál es el giro de su negocio?')
                    ->required()
                    ->validationMessages(['required' => 'Este campo es obligatorio.']),
                    
                Forms\Components\Textarea::make('experiencia_giro')
                    ->label('Describa brevemente su experiencia en el giro')
                    ->rows(3)
                    ->required()
                    ->columnSpanFull()
                    ->validationMessages(['required' => 'Este campo es obligatorio.']),
                    
                Forms\Components\Textarea::make('propositos_arrendamiento')
                    ->label('Propósitos del arrendamiento')
                    ->rows(3)
                    ->maxLength(255) 
                    ->required()
                    ->helperText('Establecer sucursal, oficina matriz, domicilio fiscal, etc.')
                    ->columnSpanFull()
                    ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 255 caracteres.']),
                    
                Forms\Components\Radio::make('sustituye_otro_domicilio')
                    ->label('¿Este inmueble sustituirá otro domicilio?')
                    ->options([0 => 'No', 1 => 'Sí'])
                    ->required()
                    ->live()
                    ->columnSpanFull()
                    ->validationMessages(['required' => 'Este campo es obligatorio.']),

                Forms\Components\Group::make([
                    Forms\Components\Placeholder::make('dom_ant_moral_label')->label('Información del domicilio anterior')->columnSpanFull(),
                    
                    Forms\Components\TextInput::make('domicilio_anterior_calle')
                        ->label('Calle')
                        ->maxLength(100) 
                        ->required()
                        ->columnSpanFull()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 100 caracteres.']),
                        
                    Forms\Components\TextInput::make('domicilio_anterior_numero_exterior')
                        ->label('Núm Ext')
                        ->maxLength(50) 
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 50 caracteres.']),
                        
                    Forms\Components\TextInput::make('domicilio_anterior_numero_interior')
                        ->label('Núm Int')
                        ->maxLength(100) 
                        ->validationMessages(['max' => 'Este campo no puede tener más de 100 caracteres.']),
                        
                    Forms\Components\TextInput::make('domicilio_anterior_codigo_postal')
                        ->label('C.P.')
                        ->length(5)
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'length' => 'Este campo debe tener 5 dígitos.']),
                        
                    Forms\Components\TextInput::make('domicilio_anterior_colonia')
                        ->label('Colonia')
                        ->maxLength(100)
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 100 caracteres.']),
                        
                    Forms\Components\TextInput::make('domicilio_anterior_delegacion_municipio')
                        ->label('Municipio')
                        ->maxLength(100)
                        ->required()
                        ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 100 caracteres.']),
                        
                    Forms\Components\Select::make('domicilio_anterior_estado')
                        ->label('Estado')
                        ->options(\App\Helpers\EstadosMexico::getEstados())
                        ->required()
                        ->searchable()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),
                        
                    Forms\Components\Textarea::make('motivo_cambio_domicilio')
                        ->label('Motivo del cambio')
                        ->required()
                        ->columnSpanFull()
                        ->validationMessages(['required' => 'Este campo es obligatorio.']),
                ])->columns(2)->visible(fn (Forms\Get $get) => $get('sustituye_otro_domicilio') == 1)
            ])
            ->visible(function ($record) {
                if (!$record || !$record->rent) return false;
                return $record->rent->tipo_inmueble === 'comercial';
            })->columnSpanFull(),

            // ==========================================
            // GRUPO 2: USO RESIDENCIAL 
            // ==========================================
            Forms\Components\Group::make([
                Forms\Components\Placeholder::make('uso_residencial_info_moral')
                    ->label('DATOS RESIDENCIALES (EMPRESA)')
                    ->content('Información sobre los ocupantes del inmueble.')
                    ->columnSpanFull(),
                    
                Forms\Components\Section::make('Ocupantes')
                    ->schema([
                        Forms\Components\TextInput::make('numero_adultos')
                            ->label('Número de adultos que ocuparán el inmueble')
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->required()
                            ->validationMessages(['required' => 'Este campo es obligatorio.', 'integer' => 'Este campo debe ser un número entero.', 'min' => 'Este campo no puede ser negativo.']),
                            
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('nombre_adulto_1')
                                ->label('Nombre completo del adulto 1')
                                ->maxLength(100) 
                                ->validationMessages(['max' => 'Este campo no puede tener más de 100 caracteres.']),
                                
                            Forms\Components\TextInput::make('nombre_adulto_2')
                                ->label('Nombre completo del adulto 2')
                                ->maxLength(100)
                                ->validationMessages(['max' => 'Este campo no puede tener más de 100 caracteres.']),
                                
                            Forms\Components\TextInput::make('nombre_adulto_3')
                                ->label('Nombre completo del adulto 3')
                                ->maxLength(100)
                                ->validationMessages(['max' => 'Este campo no puede tener más de 100 caracteres.']),
                                
                            Forms\Components\TextInput::make('nombre_adulto_4')
                                ->label('Nombre completo del adulto 4')
                                ->maxLength(100)
                                ->validationMessages(['max' => 'Este campo no puede tener más de 100 caracteres.']),
                        ]),
                        
                        Forms\Components\Radio::make('tiene_menores')
                            ->label('¿Habitarán menores de edad?')
                            ->options([0 => 'No', 1 => 'Sí'])
                            ->required()
                            ->inline()
                            ->live()
                            ->validationMessages(['required' => 'Este campo es obligatorio.']),
                            
                        Forms\Components\TextInput::make('cuantos_menores')
                            ->label('¿Cuántos?')
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->required(fn (Forms\Get $get) => $get('tiene_menores') == 1)
                            ->visible(fn (Forms\Get $get) => $get('tiene_menores') == 1)
                            ->validationMessages(['required' => 'Este campo es obligatorio.', 'integer' => 'Este campo debe ser un número entero.', 'min' => 'Este campo no puede ser negativo.']),
                    ]),
                    
                Forms\Components\Section::make('Mascotas')
                    ->schema([
                        Forms\Components\Radio::make('tiene_mascotas')
                            ->label('¿Tienen mascotas?')
                            ->options([0 => 'No', 1 => 'Sí'])
                            ->required()
                            ->inline()
                            ->live()
                            ->validationMessages(['required' => 'Este campo es obligatorio.']),
                            
                        Forms\Components\TextInput::make('especificar_mascotas')
                            ->label('Especifique')
                            ->placeholder('Ej: 1 perro')
                            ->required(fn (Forms\Get $get) => $get('tiene_mascotas') == 1)
                            ->visible(fn (Forms\Get $get) => $get('tiene_mascotas') == 1)
                            ->columnSpanFull()
                            ->validationMessages(['required' => 'Este campo es obligatorio.']),
                    ]),
            ])
            ->visible(function ($record) {
                if (!$record || !$record->rent) return false;
                return $record->rent->tipo_inmueble === 'residencial';
            })->columnSpanFull(),
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

                    // ==========================================
                    // REFERENCIA COMERCIAL 1
                    // ==========================================
                    Forms\Components\Group::make()
                        ->schema([
                            Forms\Components\Placeholder::make('referencia_comercial1_label')->label('Referencia comercial 1')->columnSpanFull(),
                            
                            Forms\Components\TextInput::make('referencia_comercial1_empresa')
                                ->label('Nombre de la empresa:')
                                ->maxLength(200) 
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 200 caracteres.']),
                                
                            Forms\Components\TextInput::make('referencia_comercial1_contacto')
                                ->label('Nombre del contacto')
                                ->maxLength(200) 
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 200 caracteres.']),
                                
                            Forms\Components\TextInput::make('referencia_comercial1_telefono')
                                ->label('Teléfono')
                                ->tel()
                                ->length(10) 
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'length' => 'Este campo debe tener 10 dígitos.']),
                        ])->columns(2)->columnSpanFull(),

                    // ==========================================
                    // REFERENCIA COMERCIAL 2
                    // ==========================================
                    Forms\Components\Group::make()
                        ->schema([
                            Forms\Components\Placeholder::make('referencia_comercial2_label')->label('Referencia comercial 2')->columnSpanFull(),
                            
                            Forms\Components\TextInput::make('referencia_comercial2_empresa')
                                ->label('Nombre de la empresa:')
                                ->maxLength(200)
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 200 caracteres.']),
                                
                            Forms\Components\TextInput::make('referencia_comercial2_contacto')
                                ->label('Nombre del contacto')
                                ->maxLength(200)
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 200 caracteres.']),
                                
                            Forms\Components\TextInput::make('referencia_comercial2_telefono')
                                ->label('Teléfono')
                                ->tel()
                                ->length(10)
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'length' => 'Este campo debe tener 10 dígitos.']),
                        ])->columns(2)->columnSpanFull(),

                    // ==========================================
                    // REFERENCIA COMERCIAL 3
                    // ==========================================
                    Forms\Components\Group::make()
                        ->schema([
                            Forms\Components\Placeholder::make('referencia_comercial3_label')->label('Referencia comercial 3')->columnSpanFull(),
                            
                            Forms\Components\TextInput::make('referencia_comercial3_empresa')
                                ->label('Nombre de la empresa:')
                                ->maxLength(200)
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 200 caracteres.']),
                                
                            Forms\Components\TextInput::make('referencia_comercial3_contacto')
                                ->label('Nombre del contacto')
                                ->maxLength(200)
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'max' => 'Este campo no puede tener más de 200 caracteres.']),
                                
                            Forms\Components\TextInput::make('referencia_comercial3_telefono')
                                ->label('Teléfono')
                                ->tel()
                                ->length(10)
                                ->required()
                                ->validationMessages(['required' => 'Este campo es obligatorio.', 'length' => 'Este campo debe tener 10 dígitos.']),
                        ])->columns(2)->columnSpanFull(),
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
            'index' => Pages\ListTenantRequests::route('/'),
            'edit' => Pages\EditTenantRequest::route('/{record}/edit'),
        ];
    }
}