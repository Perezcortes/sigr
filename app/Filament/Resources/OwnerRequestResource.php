<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OwnerRequestResource\Pages;
use App\Models\OwnerRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

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

                // SECCIÓN: DATOS DEL INMUEBLE A ARRENDAR
                Forms\Components\Section::make('Datos del Inmueble a Arrendar')
                    ->description('Información acerca del inmueble')
                    ->schema(self::getDatosInmuebleSchema())
                    ->columns(2)
                    ->collapsible(),

                // SECCIÓN: DIRECCIÓN DEL INMUEBLE
                Forms\Components\Section::make('Dirección del Inmueble a Arrendar')
                    ->schema(self::getDireccionInmuebleSchema())
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