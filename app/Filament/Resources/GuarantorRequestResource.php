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
                Forms\Components\Section::make('Información Base')
                    ->schema([
                        Forms\Components\Select::make('estatus')
                            ->options(['nueva' => 'Nueva', 'en_proceso' => 'En Proceso', 'completada' => 'Completada', 'rechazada' => 'Rechazada'])
                            ->required()->default('nueva'),
                        Forms\Components\Radio::make('tipo_persona')
                            ->label('Tipo de Persona')
                            ->options(['fisica' => 'Persona Física', 'moral' => 'Persona Moral'])
                            ->required()->live(),
                        Forms\Components\Radio::make('tipo_figura')
                            ->label('Tipo')
                            ->options(['Obligado solidario' => 'Obligado solidario', 'Fiador' => 'Fiador'])
                            ->required(),
                    ])->columns(3),

                // SECCIONES PERSONA FÍSICA
                Forms\Components\Section::make('1. Datos Personales')
                    ->schema(self::getDatosPersonalesFisica())
                    ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                    ->columns(2)->collapsible(),

                Forms\Components\Section::make('2. Datos de Empleo e Ingresos')
                    ->schema(self::getDatosEmpleoFisica())
                    ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                    ->columns(2)->collapsible(),

                // SECCIONES PERSONA MORAL
                Forms\Components\Section::make('1. Datos de la Empresa')
                    ->schema(self::getDatosEmpresaMoral())
                    ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                    ->columns(2)->collapsible(),

                // SECCIÓN COMPARTIDA: PROPIEDAD EN GARANTÍA
                Forms\Components\Section::make('Propiedad en garantía (Opcional)')
                    ->schema(self::getPropiedadGarantia())
                    ->columns(2)->collapsible(),
            ]);
    }

    // --- MÉTODOS DE ESQUEMA PRIVADOS  ---

    protected static function getDatosPersonalesFisica(): array
    {
        return [
            Forms\Components\TextInput::make('nombres')->label('Nombre(s)')->required(),
            Forms\Components\TextInput::make('primer_apellido')->label('Apellido Paterno')->required(),
            Forms\Components\TextInput::make('segundo_apellido')->label('Apellido Materno'),
            Forms\Components\Radio::make('nacionalidad')->options(['Mexicana' => 'Mexicana', 'Extranjera' => 'Extranjera'])->live(),
            Forms\Components\TextInput::make('nacionalidad_especifica')->label('Especifique')->visible(fn(Forms\Get $get) => $get('nacionalidad') === 'Extranjera'),
            Forms\Components\Select::make('sexo')->options(['Masculino' => 'Masculino', 'Femenino' => 'Femenino'])->label('Sexo'),
            Forms\Components\Radio::make('estado_civil')->options(['Soltero' => 'Soltero', 'Casado' => 'Casado'])->live(),
            Forms\Components\DatePicker::make('fecha_nacimiento')->label('Fecha de nacimiento')->displayFormat('d/m/Y'),
            Forms\Components\Select::make('tipo_identificacion')->options(['INE' => 'INE', 'Pasaporte' => 'Pasaporte', 'Cedula' => 'Cédula', 'Licencia' => 'Licencia', 'Otro' => 'Otro'])->label('Identificación'),
            Forms\Components\TextInput::make('curp')->label('CURP')->maxLength(18),
            Forms\Components\TextInput::make('rfc')->label('RFC')->maxLength(13),
            Forms\Components\TextInput::make('email')->email()->label('E-mail'),
            Forms\Components\TextInput::make('email_confirmacion')->email()->label('Confirmar E-mail')->same('email'),
            Forms\Components\TextInput::make('telefono_celular')->tel()->label('Celular'),
            Forms\Components\TextInput::make('telefono_fijo')->tel()->label('Teléfono (Si no tiene, repita celular)'),
            Forms\Components\TextInput::make('relacion_solicitante')->label('Relación con el solicitante'),
            Forms\Components\TextInput::make('tiempo_conocerlo')->label('Tiempo de conocerlo'),

            // Datos del Cónyuge (Solo aparece si selecciona Casado)
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
                ->visible(fn (Forms\Get $get) => $get('estado_civil') === 'Casado')
                ->columnSpanFull(),
            
            Forms\Components\Fieldset::make('Domicilio donde vive actualmente')->schema([
                Forms\Components\TextInput::make('calle')->label('Calle')->columnSpanFull(),
                Forms\Components\TextInput::make('numero_exterior')->label('Número exterior'),
                Forms\Components\TextInput::make('numero_interior')->label('Número interior'),
                Forms\Components\TextInput::make('codigo_postal')->label('Código postal'),
                Forms\Components\TextInput::make('colonia')->label('Colonia'),
                Forms\Components\TextInput::make('municipio')->label('Delegación / Municipio'),
                Forms\Components\Select::make('estado')->label('Estado')->options(\App\Helpers\EstadosMexico::getEstados())->searchable(),
                
                Forms\Components\Radio::make('es_domicilio_fiscal')
                    ->label('¿Este domicilio es el mismo registrado como domicilio fiscal ante el SAT?')
                    ->options([1 => 'Sí', 0 => 'No'])
                    ->live()->columnSpanFull(),
            ]),

            Forms\Components\Fieldset::make('Domicilio Fiscal')->schema([
                Forms\Components\TextInput::make('fiscal_calle')->label('Calle')->columnSpanFull(),
                Forms\Components\TextInput::make('fiscal_numero_exterior')->label('Número exterior'),
                Forms\Components\TextInput::make('fiscal_numero_interior')->label('Número interior'),
                Forms\Components\TextInput::make('fiscal_codigo_postal')->label('Código postal'),
                Forms\Components\TextInput::make('fiscal_colonia')->label('Colonia'),
                Forms\Components\TextInput::make('fiscal_municipio')->label('Delegación / Municipio'),
                Forms\Components\Select::make('fiscal_estado')->label('Estado')->options(\App\Helpers\EstadosMexico::getEstados())->searchable(),
            ])->visible(fn(Forms\Get $get) => $get('es_domicilio_fiscal') == '0')->columnSpanFull(),
        ];
    }

    protected static function getDatosEmpleoFisica(): array
    {
        return [
            Forms\Components\TextInput::make('empresa_trabaja')->label('Empresa donde trabaja'),
            Forms\Components\DatePicker::make('fecha_ingreso_empleo')->label('Fecha de Ingreso'),
            Forms\Components\TextInput::make('profesion_puesto')->label('Profesión, oficio o puesto'),
            Forms\Components\Select::make('tipo_empleo')->options(['Dueño de negocio' => 'Dueño de negocio', 'Empresario' => 'Empresario', 'Independiente' => 'Independiente', 'Empleado' => 'Empleado', 'Comisionista' => 'Comisionista', 'Jubilado' => 'Jubilado']),
            Forms\Components\Select::make('regimen_fiscal')->options(['Asalariado' => 'Asalariado', 'Actividad empresarial' => 'Actividad empresarial', 'Honorarios' => 'Honorarios', 'No aplica' => 'No aplica']),
            Forms\Components\TextInput::make('ingreso_mensual')->numeric()->prefix('$')->label('Ingreso mensual'),
            
            Forms\Components\Fieldset::make('Ubicación de la empresa donde labora')->schema([
                Forms\Components\TextInput::make('empresa_calle')->label('Calle')->columnSpanFull(),
                Forms\Components\TextInput::make('empresa_numero_exterior')->label('Número exterior'),
                Forms\Components\TextInput::make('empresa_numero_interior')->label('Número interior'),
                Forms\Components\TextInput::make('empresa_codigo_postal')->label('Código postal'),
                Forms\Components\TextInput::make('empresa_colonia')->label('Colonia'),
                Forms\Components\TextInput::make('empresa_municipio')->label('Delegación / Municipio'),
                Forms\Components\Select::make('empresa_estado')->label('Estado')->options(\App\Helpers\EstadosMexico::getEstados())->searchable(),
                Forms\Components\TextInput::make('empresa_telefono')->tel()->label('Teléfono'),
                Forms\Components\TextInput::make('empresa_extension')->label('No. de extensión'),
            ]),

            Forms\Components\Checkbox::make('autoriza_buro')->label('Autorizo a investigar los datos por cualquier medio legal, incluyendo buró de crédito.')->columnSpanFull(),
            Forms\Components\Checkbox::make('acepta_protesta')->label('Declaro bajo portesta de decir verdad que los datos asentados son correctos y acepto que será causa de rescisión del contrato de arrendamiento la falsedad de cualquiera de ellos.')->columnSpanFull(),
        ];
    }

    protected static function getDatosEmpresaMoral(): array
    {
        return [
            Forms\Components\TextInput::make('razon_social')->label('Nombre de la empresa'),
            Forms\Components\TextInput::make('rfc')->label('RFC'),
            Forms\Components\TextInput::make('email')->email()->label('E-Mail'),
            Forms\Components\TextInput::make('telefono')->tel()->label('Teléfono'),
            Forms\Components\Select::make('regimen_fiscal')->options(['Asalariado' => 'Asalariado', 'Actividad empresarial' => 'Actividad empresarial', 'Honorarios' => 'Honorarios', 'No aplica' => 'No aplica']),
            Forms\Components\TextInput::make('antiguedad_empresa')->label('Antigüedad de la empresa'),
            Forms\Components\TextInput::make('ingreso_mensual')->numeric()->prefix('$')->label('Ingreso mensual aproximado'),
            Forms\Components\Textarea::make('actividades_empresa')->label('Especifique breve y claramente las actividades de la Empresa y cómo obtienen sus ingresos')->columnSpanFull(),

            Forms\Components\Fieldset::make('Domicilio de la empresa')->schema([
                Forms\Components\TextInput::make('calle')->label('Calle')->columnSpanFull(),
                Forms\Components\TextInput::make('numero_exterior')->label('Número exterior'),
                Forms\Components\TextInput::make('numero_interior')->label('Número interior'),
                Forms\Components\TextInput::make('codigo_postal')->label('Código postal'),
                Forms\Components\TextInput::make('colonia')->label('Colonia'),
                Forms\Components\TextInput::make('municipio')->label('Delegación / Municipio'),
                Forms\Components\Select::make('estado')->label('Estado')->options(\App\Helpers\EstadosMexico::getEstados())->searchable(),
                
                Forms\Components\Radio::make('es_domicilio_fiscal')
                    ->label('¿Este domicilio es el mismo registrado como domicilio fiscal ante el SAT?')
                    ->options([1 => 'Sí', 0 => 'No'])
                    ->live()->columnSpanFull(),
            ]),

            Forms\Components\Fieldset::make('Domicilio Fiscal')->schema([
                Forms\Components\TextInput::make('fiscal_calle')->label('Calle')->columnSpanFull(),
                Forms\Components\TextInput::make('fiscal_numero_exterior')->label('Número exterior'),
                Forms\Components\TextInput::make('fiscal_numero_interior')->label('Número interior'),
                Forms\Components\TextInput::make('fiscal_codigo_postal')->label('Código postal'),
                Forms\Components\TextInput::make('fiscal_colonia')->label('Colonia'),
                Forms\Components\TextInput::make('fiscal_municipio')->label('Delegación / Municipio'),
                Forms\Components\Select::make('fiscal_estado')->label('Estado')->options(\App\Helpers\EstadosMexico::getEstados())->searchable(),
            ])->visible(fn(Forms\Get $get) => $get('es_domicilio_fiscal') == '0')->columnSpanFull(),

            Forms\Components\Fieldset::make('Datos del acta constitutiva')->schema([
                Forms\Components\TextInput::make('notario_nombres')->label('Nombre(s) del notario'),
                Forms\Components\TextInput::make('notario_apellidos')->label('Apellidos del notario'),
                Forms\Components\TextInput::make('numero_escritura')->label('No. de escritura'),
                Forms\Components\DatePicker::make('fecha_constitucion')->label('Fecha de constitución'),
                Forms\Components\TextInput::make('notario_numero')->label('Notario número'),
                Forms\Components\TextInput::make('ciudad_registro')->label('Ciudad de registro'),
                Forms\Components\Select::make('estado_registro')->label('Estado de registro')->options(\App\Helpers\EstadosMexico::getEstados()),
                Forms\Components\TextInput::make('numero_inscripcion_pm')->label('Número de registro o inscripción de la persona moral'),
                Forms\Components\TextInput::make('giro_comercial')->label('Giro comercial'),
            ]),

            Forms\Components\Fieldset::make('Información sobre el Representante Legal')->schema([
                Forms\Components\TextInput::make('rep_nombres')->label('Nombre(s)'),
                Forms\Components\TextInput::make('rep_primer_apellido')->label('Apellido Paterno'),
                Forms\Components\TextInput::make('rep_segundo_apellido')->label('Apellido Materno'),
                Forms\Components\Select::make('rep_sexo')->options(['Masculino' => 'Masculino', 'Femenino' => 'Femenino']),
                Forms\Components\TextInput::make('rep_rfc')->label('RFC'),
                Forms\Components\TextInput::make('rep_curp')->label('CURP'),
                Forms\Components\TextInput::make('rep_email')->email()->label('E-Mail'),
                Forms\Components\TextInput::make('rep_telefono')->tel()->label('Teléfono'),
                // Domicilio breve del representante
                Forms\Components\TextInput::make('rep_calle')->label('Calle Rep.'),
                Forms\Components\TextInput::make('rep_numero_exterior')->label('Num Ext Rep.'),
                Forms\Components\TextInput::make('rep_codigo_postal')->label('CP Rep.'),
                Forms\Components\TextInput::make('rep_colonia')->label('Colonia Rep.'),
            ]),

            Forms\Components\Fieldset::make('Facultades')->schema([
                Forms\Components\Radio::make('facultades_en_acta')
                    ->label('¿Sus facultades constan en el acta constitutiva de la empresa?')
                    ->options([1 => 'Sí', 0 => 'No'])
                    ->live()->columnSpanFull(),
                
                Forms\Components\Group::make([
                    Forms\Components\Placeholder::make('fac_aviso')->content(' Deberá contar con facultades para obligarse a nombre de la sociedad ante terceros o para firmar contratos de arrendamiento y con facultades para otorgar y suscribir títulos de crédito.')->columnSpanFull(),
                    Forms\Components\TextInput::make('fac_escritura')->label('Escritura pública o acta número'),
                    Forms\Components\TextInput::make('fac_notario')->label('Notario número'),
                    Forms\Components\DatePicker::make('fac_fecha_escritura')->label('Fecha de escritura o acta'),
                    Forms\Components\TextInput::make('fac_inscripcion')->label('No. de inscripción en el RP'),
                    Forms\Components\DatePicker::make('fac_fecha_inscripcion')->label('Fecha de inscripción'),
                    Forms\Components\TextInput::make('fac_ciudad')->label('Ciudad de registro'),
                    Forms\Components\Select::make('fac_estado')->label('Estado de registro')->options(\App\Helpers\EstadosMexico::getEstados()),
                    Forms\Components\Select::make('fac_tipo_representacion')->label('Tipo de representación')->options(['Administrador único' => 'Administrador único', 'Presidente del consejo' => 'Presidente del consejo', 'Socio administrador' => 'Socio administrador', 'Gerente' => 'Gerente', 'Otro' => 'Otro'])->live(),
                    Forms\Components\TextInput::make('fac_representacion_otro')->label('Llenar en caso de otro')->visible(fn(Forms\Get $get) => $get('fac_tipo_representacion') === 'Otro'),
                ])->visible(fn(Forms\Get $get) => $get('facultades_en_acta') == '0')->columns(2)->columnSpanFull(),
            ])->columnSpanFull(),
        ];
    }

    protected static function getPropiedadGarantia(): array
    {
        return [
            Forms\Components\Fieldset::make('Domicilio de la propiedad en garantía')->schema([
                Forms\Components\TextInput::make('garantia_calle')->label('Calle')->columnSpanFull(),
                Forms\Components\TextInput::make('garantia_numero_exterior')->label('Número exterior'),
                Forms\Components\TextInput::make('garantia_numero_interior')->label('Número interior'),
                Forms\Components\TextInput::make('garantia_codigo_postal')->label('Código postal'),
                Forms\Components\TextInput::make('garantia_colonia')->label('Colonia'),
                Forms\Components\TextInput::make('garantia_municipio')->label('Delegación / Municipio'),
                Forms\Components\Select::make('garantia_estado')->label('Estado')->options(\App\Helpers\EstadosMexico::getEstados())->searchable(),
            ]),
            Forms\Components\Fieldset::make('Datos de la escritura')->schema([
                Forms\Components\TextInput::make('garantia_num_escritura')->label('Número de escritura'),
                Forms\Components\DatePicker::make('garantia_fecha_escritura')->label('Fecha de escritura'),
            ]),
            Forms\Components\Fieldset::make('Información del notario')->schema([
                Forms\Components\TextInput::make('garantia_notario_nombres')->label('Nombre(s) del notario'),
                Forms\Components\TextInput::make('garantia_notario_paterno')->label('Apellido Paterno'),
                Forms\Components\TextInput::make('garantia_notario_materno')->label('Apellido Materno'),
                Forms\Components\TextInput::make('garantia_num_notaria')->label('Número de notaría'),
                Forms\Components\TextInput::make('garantia_lugar_notaria')->label('Lugar de la notaría'),
            ]),
            Forms\Components\Fieldset::make('Registro público de propiedad')->schema([
                Forms\Components\TextInput::make('garantia_rpp')->label('Registro Público de la Propiedad'),
                Forms\Components\TextInput::make('garantia_folio_real')->label('Folio real electrónico'),
                Forms\Components\DatePicker::make('garantia_fecha_rpp')->label('Fecha'),
                Forms\Components\TextInput::make('garantia_boleta_predial')->label('No. de Boleta Predial'),
            ]),
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