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

                // SECCIÓN: DATOS DEL PROPIETARIO (Persona Física)
                Forms\Components\Section::make('Datos del Propietario')
                    ->description('Información personal acerca del propietario')
                    ->schema(self::getDatosPropietarioFisicaSchema())
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

                Forms\Components\Section::make('Datos del Acta Constitutiva')
                    ->schema(self::getActaConstitutivaSchema())
                    ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Apoderado Legal y/o Representante')
                    ->schema(self::getApoderadoSchema())
                    ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                    ->columns(2)
                    ->collapsible(),

                // SECCIÓN: PROPIEDAD VINCULADA A LA RENTA
                Forms\Components\Section::make('Propiedad vinculada a la renta')
                    ->description('Selecciona una propiedad existente del propietario o da de alta una nueva. Esto solo vincula la propiedad a la renta.')
                    ->schema(self::getLinkedPropertySchema())
                    ->columns(1)
                    ->collapsible(),

                Forms\Components\Section::make('Imágenes de la Propiedad Vinculada')
                    ->description('Carga y administra imágenes de la propiedad seleccionada.')
                    ->schema(self::getPropertyImagesSchema())
                    ->columns(1)
                    ->collapsible()
                    ->visible(fn (Forms\Get $get) => filled($get('selected_property_id'))),

                // SECCIÓN: DATOS DEL INMUEBLE A ARRENDAR
                Forms\Components\Section::make('Datos del Inmueble a Arrendar')
                    ->description('Información acerca del inmueble')
                    ->schema(self::getDatosInmuebleSchema())
                    ->visible(fn (Forms\Get $get) => blank($get('selected_property_id')))
                    ->columns(2)
                    ->collapsible(),

                // SECCIÓN: DIRECCIÓN DEL INMUEBLE
                Forms\Components\Section::make('Dirección del Inmueble a Arrendar')
                    ->schema(self::getDireccionInmuebleSchema())
                    ->visible(fn (Forms\Get $get) => blank($get('selected_property_id')))
                    ->columns(2)
                    ->collapsible(),

                // SECCIÓN: REPRESENTACIÓN (solo para Persona Física)
                Forms\Components\Section::make('Representación del Propietario')
                    ->description('Llenar solo si el propietario será representado por un tercero')
                    ->schema(self::getRepresentacionSchema())
                    ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    protected static function getDatosPropietarioFisicaSchema(): array
    {
        return [
            Forms\Components\TextInput::make('nombres')
                ->label('Nombre(s)')
                ->required(),

            Forms\Components\TextInput::make('primer_apellido')
                ->label('Apellido Paterno')
                ->required(),

            Forms\Components\TextInput::make('segundo_apellido')
                ->label('Apellido Materno'),

            Forms\Components\TextInput::make('curp')
                ->label('CURP')
                ->maxLength(18)
                ->required(),

            Forms\Components\TextInput::make('email')
                ->label('Correo electrónico')
                ->email()
                ->required(),

            Forms\Components\TextInput::make('telefono')
                ->label('Teléfono')
                ->tel()
                ->required(),

            Forms\Components\Select::make('estado_civil')
                ->label('Estado civil')
                ->options([
                    'Casado' => 'Casado',
                    'Divorciado' => 'Divorciado',
                    'Soltero' => 'Soltero',
                    'Union libre' => 'Unión libre',
                ])
                ->required()
                ->live(),

            Forms\Components\Select::make('regimen_conyugal')
                ->label('En caso de ser casado bajo qué régimen')
                ->options([
                    'Sociedad conyugal' => 'Sociedad conyugal',
                    'Separacion de bienes' => 'Separación de bienes',
                ])
                ->required(fn (Forms\Get $get) => $get('estado_civil') === 'Casado')
                ->visible(fn (Forms\Get $get) => $get('estado_civil') === 'Casado'),

            Forms\Components\Select::make('sexo')
                ->label('Sexo')
                ->options([
                    'Masculino' => 'Masculino',
                    'Femenino' => 'Femenino',
                ])
                ->required(),

            Forms\Components\Select::make('nacionalidad')
                ->label('Nacionalidad')
                ->options([
                    'Mexicana' => 'Mexicana',
                    'Extranjera' => 'Extranjera',
                ])
                ->required(),

            Forms\Components\Select::make('tipo_identificacion')
                ->label('Identificación')
                ->options([
                    'INE' => 'INE',
                    'Pasaporte' => 'Pasaporte',
                ])
                ->required(),

            Forms\Components\TextInput::make('rfc')
                ->label('RFC')
                ->maxLength(13)
                ->required(),

            // Domicilio
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

            Forms\Components\Textarea::make('referencias_ubicacion')
                ->label('Referencias para ubicar el domicilio')
                ->rows(3)
                ->required()
                ->columnSpanFull(),

            // Forma de Pago
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
                ->columnSpanFull(),

            Forms\Components\TextInput::make('forma_pago_otro')
                ->label('En caso de otro, especifique')
                ->required(fn (Forms\Get $get) => $get('forma_pago') === 'Otro')
                ->visible(fn (Forms\Get $get) => $get('forma_pago') === 'Otro')
                ->columnSpanFull(),

            // Datos de Transferencia (solo si selecciona Transferencia)
            Forms\Components\TextInput::make('titular_cuenta')
                ->label('Titular de la cuenta')
                ->required(fn (Forms\Get $get) => $get('forma_pago') === 'Transferencia')
                ->visible(fn (Forms\Get $get) => $get('forma_pago') === 'Transferencia'),

            Forms\Components\TextInput::make('numero_cuenta')
                ->label('Número de cuenta')
                ->required(fn (Forms\Get $get) => $get('forma_pago') === 'Transferencia')
                ->visible(fn (Forms\Get $get) => $get('forma_pago') === 'Transferencia'),

            Forms\Components\TextInput::make('nombre_banco')
                ->label('Nombre del banco')
                ->required(fn (Forms\Get $get) => $get('forma_pago') === 'Transferencia')
                ->visible(fn (Forms\Get $get) => $get('forma_pago') === 'Transferencia'),

            Forms\Components\TextInput::make('clabe_interbancaria')
                ->label('CLABE interbancaria')
                ->maxLength(18)
                ->required(fn (Forms\Get $get) => $get('forma_pago') === 'Transferencia')
                ->visible(fn (Forms\Get $get) => $get('forma_pago') === 'Transferencia'),
        ];
    }

    protected static function getDatosEmpresaSchema(): array
    {
        return [
            Forms\Components\TextInput::make('razon_social')
                ->label('Nombre de la empresa')
                ->required(),

            Forms\Components\TextInput::make('rfc')
                ->label('RFC')
                ->maxLength(13)
                ->required(),

            Forms\Components\TextInput::make('email')
                ->label('Correo Electrónico')
                ->email()
                ->required(),

            Forms\Components\TextInput::make('telefono')
                ->label('Teléfono')
                ->tel()
                ->required(),

            // Domicilio de la Empresa
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

            Forms\Components\Textarea::make('referencias_ubicacion')
                ->label('Referencias para ubicar la empresa')
                ->rows(3)
                ->required()
                ->columnSpanFull(),

            // Forma de Pago
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
                ->columnSpanFull(),

            Forms\Components\TextInput::make('forma_pago_otro')
                ->label('En caso de otro, especifique')
                ->required(fn (Forms\Get $get) => $get('forma_pago') === 'Otro')
                ->visible(fn (Forms\Get $get) => $get('forma_pago') === 'Otro')
                ->columnSpanFull(),

            // Datos de Transferencia (solo si selecciona Transferencia)
            Forms\Components\TextInput::make('titular_cuenta')
                ->label('Titular de la cuenta')
                ->required(fn (Forms\Get $get) => $get('forma_pago') === 'Transferencia')
                ->visible(fn (Forms\Get $get) => $get('forma_pago') === 'Transferencia'),

            Forms\Components\TextInput::make('numero_cuenta')
                ->label('Número de cuenta')
                ->required(fn (Forms\Get $get) => $get('forma_pago') === 'Transferencia')
                ->visible(fn (Forms\Get $get) => $get('forma_pago') === 'Transferencia'),

            Forms\Components\TextInput::make('nombre_banco')
                ->label('Nombre del banco')
                ->required(fn (Forms\Get $get) => $get('forma_pago') === 'Transferencia')
                ->visible(fn (Forms\Get $get) => $get('forma_pago') === 'Transferencia'),

            Forms\Components\TextInput::make('clabe_interbancaria')
                ->label('CLABE interbancaria')
                ->maxLength(18)
                ->required(fn (Forms\Get $get) => $get('forma_pago') === 'Transferencia')
                ->visible(fn (Forms\Get $get) => $get('forma_pago') === 'Transferencia'),
        ];
    }

    protected static function getActaConstitutivaSchema(): array
    {
        return [
            Forms\Components\TextInput::make('notario_nombres')
                ->label('Nombre(s) del Notario')
                ->required(),

            Forms\Components\TextInput::make('notario_primer_apellido')
                ->label('Apellido Paterno')
                ->required(),

            Forms\Components\TextInput::make('notario_segundo_apellido')
                ->label('Apellido Materno'),

            Forms\Components\TextInput::make('numero_escritura')
                ->label('No. de Escritura')
                ->required(),

            Forms\Components\DatePicker::make('fecha_constitucion')
                ->label('Fecha de Constitución')
                ->displayFormat('d/m/Y')
                ->required()
                ->native(false),

            Forms\Components\TextInput::make('notario_numero')
                ->label('Notario Número')
                ->required(),

            Forms\Components\TextInput::make('ciudad_registro')
                ->label('Ciudad de Registro')
                ->required(),

            Forms\Components\Select::make('estado_registro')
                ->label('Estado de Registro')
                ->options(\App\Helpers\EstadosMexico::getEstados())
                ->required()
                ->searchable(),

            Forms\Components\TextInput::make('numero_registro_inscripcion')
                ->label('Número de Registro o Inscripción')
                ->required(),

            Forms\Components\TextInput::make('giro_comercial')
                ->label('Giro Comercial')
                ->required(),
        ];
    }

    protected static function getApoderadoSchema(): array
    {
        return [
            Forms\Components\TextInput::make('apoderado_nombres')
                ->label('Nombre(s)')
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

            Forms\Components\TextInput::make('apoderado_curp')
                ->label('CURP')
                ->maxLength(18)
                ->required(),

            Forms\Components\TextInput::make('apoderado_email')
                ->label('Correo Electrónico')
                ->email()
                ->required(),

            Forms\Components\TextInput::make('apoderado_telefono')
                ->label('Teléfono')
                ->tel()
                ->required(),

            Forms\Components\TextInput::make('apoderado_calle')
                ->label('Calle')
                ->required(),

            Forms\Components\TextInput::make('apoderado_numero_exterior')
                ->label('Número exterior')
                ->required(),

            Forms\Components\TextInput::make('apoderado_numero_interior')
                ->label('Número interior'),

            Forms\Components\TextInput::make('apoderado_cp')
                ->label('CP')
                ->required()
                ->maxLength(5),

            Forms\Components\TextInput::make('apoderado_colonia')
                ->label('Colonia')
                ->required(),

            Forms\Components\TextInput::make('apoderado_municipio')
                ->label('Municipio')
                ->required(),

            Forms\Components\Select::make('apoderado_estado')
                ->label('Estado')
                ->options(\App\Helpers\EstadosMexico::getEstados())
                ->required()
                ->searchable(),

            Forms\Components\Radio::make('facultades_en_acta')
                ->label('¿Sus facultades constan en el acta constitutiva de la empresa?')
                ->options([
                    false => 'Sí',
                    true => 'No',
                ])
                ->required()
                ->live()
                ->columnSpanFull(),

            // Facultades en Acta (solo si es verdadero)
            Forms\Components\TextInput::make('escritura_publica_numero')
                ->label('Escritura Pública o Acta Número')
                ->required(fn (Forms\Get $get) => $get('facultades_en_acta') === true)
                ->visible(fn (Forms\Get $get) => $get('facultades_en_acta') === true),

            Forms\Components\TextInput::make('notario_numero_facultades')
                ->label('Notario Número')
                ->required(fn (Forms\Get $get) => $get('facultades_en_acta') === true)
                ->visible(fn (Forms\Get $get) => $get('facultades_en_acta') === true),

            Forms\Components\DatePicker::make('fecha_escritura_facultades')
                ->label('Fecha de Escritura o Acta')
                ->displayFormat('d/m/Y')
                ->required(fn (Forms\Get $get) => $get('facultades_en_acta') === true)
                ->visible(fn (Forms\Get $get) => $get('facultades_en_acta') === true)
                ->native(false),

            Forms\Components\TextInput::make('numero_inscripcion_registro_publico')
                ->label('No. de Inscripción en el Registro Público')
                ->required(fn (Forms\Get $get) => $get('facultades_en_acta') === true)
                ->visible(fn (Forms\Get $get) => $get('facultades_en_acta') === true),

            Forms\Components\TextInput::make('ciudad_registro_facultades')
                ->label('Ciudad de Registro')
                ->required(fn (Forms\Get $get) => $get('facultades_en_acta') === true)
                ->visible(fn (Forms\Get $get) => $get('facultades_en_acta') === true),

            Forms\Components\Select::make('estado_registro_facultades')
                ->label('Estado de Registro')
                ->options(\App\Helpers\EstadosMexico::getEstados())
                ->required(fn (Forms\Get $get) => $get('facultades_en_acta') === true)
                ->visible(fn (Forms\Get $get) => $get('facultades_en_acta') === true)
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
                ->required(fn (Forms\Get $get) => $get('facultades_en_acta') === true)
                ->visible(fn (Forms\Get $get) => $get('facultades_en_acta') === true)
                ->live(),

            Forms\Components\TextInput::make('tipo_representacion_otro')
                ->label('Llenar en caso de otro')
                ->required(fn (Forms\Get $get) => $get('tipo_representacion_moral') === 'Otro')
                ->visible(fn (Forms\Get $get) => $get('tipo_representacion_moral') === 'Otro'),
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
                ->required(),

            Forms\Components\Select::make('uso_suelo')
                ->label('Uso de suelo')
                ->options([
                    'Habitacional' => 'Habitacional',
                    'Comercial' => 'Comercial',
                    'Industrial' => 'Industrial',
                ])
                ->required(),

            Forms\Components\Select::make('mascotas')
                ->label('¿Mascotas?')
                ->options([
                    'si' => 'Sí',
                    'no' => 'No',
                ])
                ->required()
                ->live(),

            Forms\Components\TextInput::make('mascotas_especifica')
                ->label('Especifique')
                ->required(fn (Forms\Get $get) => $get('mascotas') === 'si')
                ->visible(fn (Forms\Get $get) => $get('mascotas') === 'si'),

            Forms\Components\TextInput::make('precio_renta')
                ->label('Precio de renta')
                ->numeric()
                ->prefix('$')
                ->required(),

            Forms\Components\Select::make('iva_renta')
                ->label('IVA en la renta')
                ->options([
                    'IVA incluido' => 'IVA incluido',
                    'Mas IVA' => 'Más IVA',
                    'Sin IVA' => 'Sin IVA',
                ])
                ->required(),

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
                ->live(),

            Forms\Components\TextInput::make('frecuencia_pago_otra')
                ->label('En caso de otra frecuencia')
                ->required(fn (Forms\Get $get) => $get('frecuencia_pago') === 'Otra')
                ->visible(fn (Forms\Get $get) => $get('frecuencia_pago') === 'Otra'),

            Forms\Components\Textarea::make('condiciones_pago')
                ->label('Condiciones de pago')
                ->rows(3)
                ->required()
                ->columnSpanFull(),

            Forms\Components\TextInput::make('deposito_garantia')
                ->label('Cantidad de depósito en garantía')
                ->numeric()
                ->prefix('$')
                ->required(),

            Forms\Components\Select::make('paga_mantenimiento')
                ->label('¿Se paga mantenimiento?')
                ->options([
                    'si' => 'Sí',
                    'no' => 'No',
                ])
                ->required()
                ->live(),

            Forms\Components\Select::make('quien_paga_mantenimiento')
                ->label('¿Quién paga el mantenimiento?')
                ->options([
                    'Arrendatario' => 'Arrendatario',
                    'Arrendador' => 'Arrendador',
                ])
                ->required(fn (Forms\Get $get) => $get('paga_mantenimiento') === 'si')
                ->visible(fn (Forms\Get $get) => $get('paga_mantenimiento') === 'si'),

            Forms\Components\Select::make('mantenimiento_incluido_renta')
                ->label('¿Está incluido en la renta?')
                ->options([
                    'si' => 'Sí',
                    'no' => 'No',
                ])
                ->required(fn (Forms\Get $get) => $get('paga_mantenimiento') === 'si')
                ->visible(fn (Forms\Get $get) => $get('paga_mantenimiento') === 'si'),

            Forms\Components\TextInput::make('costo_mantenimiento_mensual')
                ->label('Costo mensual del mantenimiento')
                ->numeric()
                ->prefix('$')
                ->required(fn (Forms\Get $get) => $get('paga_mantenimiento') === 'si')
                ->visible(fn (Forms\Get $get) => $get('paga_mantenimiento') === 'si'),

            Forms\Components\Textarea::make('instrucciones_pago')
                ->label('Instrucciones de pago')
                ->rows(3)
                ->required()
                ->columnSpanFull(),

            Forms\Components\Select::make('requiere_seguro')
                ->label('¿Se requiere contratar seguro?')
                ->options([
                    'si' => 'Sí',
                    'no' => 'No',
                ])
                ->required()
                ->live(),

            Forms\Components\TextInput::make('cobertura_seguro')
                ->label('¿Qué cobertura tiene?')
                ->required(fn (Forms\Get $get) => $get('requiere_seguro') === 'si')
                ->visible(fn (Forms\Get $get) => $get('requiere_seguro') === 'si'),

            Forms\Components\TextInput::make('monto_cobertura_seguro')
                ->label('Monto que cubre el seguro')
                ->numeric()
                ->prefix('$')
                ->required(fn (Forms\Get $get) => $get('requiere_seguro') === 'si')
                ->visible(fn (Forms\Get $get) => $get('requiere_seguro') === 'si'),

            Forms\Components\Textarea::make('servicios_pagar')
                ->label('Servicios que se deberán pagar del inmueble')
                ->rows(3)
                ->required()
                ->columnSpanFull(),
        ];
    }

    protected static function getDireccionInmuebleSchema(): array
    {
        return [
            Forms\Components\TextInput::make('inmueble_calle')
                ->label('Calle')
                ->required()
                ->columnSpanFull(),

            Forms\Components\TextInput::make('inmueble_numero_exterior')
                ->label('Número exterior')
                ->required(),

            Forms\Components\TextInput::make('inmueble_numero_interior')
                ->label('Número interior'),

            Forms\Components\TextInput::make('inmueble_codigo_postal')
                ->label('Código postal')
                ->required()
                ->maxLength(5),

            Forms\Components\TextInput::make('inmueble_colonia')
                ->label('Colonia')
                ->required(),

            Forms\Components\TextInput::make('inmueble_delegacion_municipio')
                ->label('Delegación / Municipio')
                ->required(),

            Forms\Components\Select::make('inmueble_estado')
                ->label('Estado')
                ->options(\App\Helpers\EstadosMexico::getEstados())
                ->required()
                ->searchable(),

            Forms\Components\Textarea::make('inmueble_referencias')
                ->label('Referencias para ubicar el domicilio')
                ->rows(3)
                ->required()
                ->columnSpanFull(),

            Forms\Components\Textarea::make('inmueble_inventario')
                ->label('Inventario del inmueble')
                ->rows(3)
                ->required()
                ->columnSpanFull()
                ->helperText('Describa el inventario con el que cuenta el inmueble, por ejemplo, cortinas, muebles, hidroneumático, etc.'),
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
                ->live()
                ->columnSpanFull(),

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
                ->columnSpanFull(),

            // Información del Representante
            Forms\Components\TextInput::make('representante_nombres')
                ->label('Nombre(s) del representante')
                ->required(fn (Forms\Get $get) => $get('sera_representado') === 'Si')
                ->visible(fn (Forms\Get $get) => $get('sera_representado') === 'Si'),

            Forms\Components\TextInput::make('representante_primer_apellido')
                ->label('Apellido Paterno')
                ->required(fn (Forms\Get $get) => $get('sera_representado') === 'Si')
                ->visible(fn (Forms\Get $get) => $get('sera_representado') === 'Si'),

            Forms\Components\TextInput::make('representante_segundo_apellido')
                ->label('Apellido Materno')
                ->required(fn (Forms\Get $get) => $get('sera_representado') === 'Si')
                ->visible(fn (Forms\Get $get) => $get('sera_representado') === 'Si'),

            Forms\Components\Select::make('representante_sexo')
                ->label('Sexo')
                ->options([
                    'Masculino' => 'Masculino',
                    'Femenino' => 'Femenino',
                ])
                ->required(fn (Forms\Get $get) => $get('sera_representado') === 'Si')
                ->visible(fn (Forms\Get $get) => $get('sera_representado') === 'Si'),

            Forms\Components\TextInput::make('representante_curp')
                ->label('CURP')
                ->maxLength(18)
                ->required(fn (Forms\Get $get) => $get('sera_representado') === 'Si')
                ->visible(fn (Forms\Get $get) => $get('sera_representado') === 'Si'),

            Forms\Components\Select::make('representante_tipo_identificacion')
                ->label('Identificación')
                ->options([
                    'INE' => 'INE',
                    'Pasaporte' => 'Pasaporte',
                ])
                ->required(fn (Forms\Get $get) => $get('sera_representado') === 'Si')
                ->visible(fn (Forms\Get $get) => $get('sera_representado') === 'Si'),

            Forms\Components\TextInput::make('representante_rfc')
                ->label('RFC')
                ->maxLength(13)
                ->required(fn (Forms\Get $get) => $get('sera_representado') === 'Si')
                ->visible(fn (Forms\Get $get) => $get('sera_representado') === 'Si'),

            Forms\Components\TextInput::make('representante_telefono')
                ->label('Teléfono')
                ->tel()
                ->required(fn (Forms\Get $get) => $get('sera_representado') === 'Si')
                ->visible(fn (Forms\Get $get) => $get('sera_representado') === 'Si'),

            Forms\Components\TextInput::make('representante_email')
                ->label('Correo electrónico')
                ->email()
                ->required(fn (Forms\Get $get) => $get('sera_representado') === 'Si')
                ->visible(fn (Forms\Get $get) => $get('sera_representado') === 'Si'),

            // Domicilio del Representante
            Forms\Components\TextInput::make('representante_calle')
                ->label('Calle')
                ->required(fn (Forms\Get $get) => $get('sera_representado') === 'Si')
                ->columnSpanFull()
                ->visible(fn (Forms\Get $get) => $get('sera_representado') === 'Si'),

            Forms\Components\TextInput::make('representante_numero_exterior')
                ->label('Número exterior')
                ->required(fn (Forms\Get $get) => $get('sera_representado') === 'Si')
                ->visible(fn (Forms\Get $get) => $get('sera_representado') === 'Si'),

            Forms\Components\TextInput::make('representante_numero_interior')
                ->label('Número interior')
                ->required(fn (Forms\Get $get) => $get('sera_representado') === 'Si')
                ->visible(fn (Forms\Get $get) => $get('sera_representado') === 'Si'),

            Forms\Components\TextInput::make('representante_codigo_postal')
                ->label('Código postal')
                ->required(fn (Forms\Get $get) => $get('sera_representado') === 'Si')
                ->maxLength(5)
                ->visible(fn (Forms\Get $get) => $get('sera_representado') === 'Si'),

            Forms\Components\TextInput::make('representante_colonia')
                ->label('Colonia')
                ->required(fn (Forms\Get $get) => $get('sera_representado') === 'Si')
                ->visible(fn (Forms\Get $get) => $get('sera_representado') === 'Si'),

            Forms\Components\TextInput::make('representante_delegacion_municipio')
                ->label('Delegación / Municipio')
                ->required(fn (Forms\Get $get) => $get('sera_representado') === 'Si')
                ->visible(fn (Forms\Get $get) => $get('sera_representado') === 'Si'),

            Forms\Components\Select::make('representante_estado')
                ->label('Estado')
                ->options(\App\Helpers\EstadosMexico::getEstados())
                ->required(fn (Forms\Get $get) => $get('sera_representado') === 'Si')
                ->searchable()
                ->visible(fn (Forms\Get $get) => $get('sera_representado') === 'Si'),

            Forms\Components\Textarea::make('representante_referencias')
                ->label('Referencias para ubicar el domicilio')
                ->rows(3)
                ->required(fn (Forms\Get $get) => $get('sera_representado') === 'Si')
                ->columnSpanFull()
                ->visible(fn (Forms\Get $get) => $get('sera_representado') === 'Si'),
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