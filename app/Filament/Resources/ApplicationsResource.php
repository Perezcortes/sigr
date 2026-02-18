<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApplicationsResource\Pages;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\Tenant;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class ApplicationsResource extends Resource
{
    protected static ?string $model = Application::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Solicitudes';
    protected static ?string $navigationGroup = 'Rentas';
    protected static ?int $navigationSort = 1;
    protected static ?string $modelLabel = 'Solicitud';
    protected static ?string $pluralModelLabel = 'Solicitudes';

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
                        Forms\Components\Select::make('user_id')
                            ->label('Inquilino')
                            ->relationship('user', 'name', modifyQueryUsing: fn (Builder $query) => $query->where('is_tenant', true))
                            ->getOptionLabelFromRecordUsing(fn (User $record) => $record->name . ' (' . $record->email . ')')
                            ->searchable(['name', 'email'])
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                if ($state) {
                                    $user = User::find($state);
                                    if ($user && $user->is_tenant) {
                                        $tenant = Tenant::where('user_id', $user->id)->first();
                                        if ($tenant) {
                                            // Pre-llenar tipo_persona desde el tenant
                                            $set('tipo_persona', $tenant->tipo_persona ?? 'fisica');
                                            
                                            // Pre-llenar campos de empleo
                                            $set('profesion_oficio_puesto', $tenant->profesion_oficio_puesto);
                                            $set('tipo_empleo', $tenant->tipo_empleo);
                                            $set('telefono_empleo', $tenant->telefono_empleo);
                                            $set('extension_empleo', $tenant->extension_empleo);
                                            $set('empresa_trabaja', $tenant->empresa_trabaja);
                                            $set('calle_empleo', $tenant->calle_empleo);
                                            $set('numero_exterior_empleo', $tenant->numero_exterior_empleo);
                                            $set('numero_interior_empleo', $tenant->numero_interior_empleo);
                                            $set('codigo_postal_empleo', $tenant->codigo_postal_empleo);
                                            $set('colonia_empleo', $tenant->colonia_empleo);
                                            $set('delegacion_municipio_empleo', $tenant->delegacion_municipio_empleo);
                                            $set('estado_empleo', $tenant->estado_empleo);
                                            $set('fecha_ingreso', $tenant->fecha_ingreso);
                                            $set('jefe_nombres', $tenant->jefe_nombres);
                                            $set('jefe_primer_apellido', $tenant->jefe_primer_apellido);
                                            $set('jefe_segundo_apellido', $tenant->jefe_segundo_apellido);
                                            $set('jefe_telefono', $tenant->jefe_telefono);
                                            $set('jefe_extension', $tenant->jefe_extension);
                                            
                                            // Pre-llenar campos de ingresos
                                            $set('ingreso_mensual_comprobable', $tenant->ingreso_mensual_comprobable);
                                            $set('ingreso_mensual_no_comprobable', $tenant->ingreso_mensual_no_comprobable);
                                            $set('numero_personas_dependen', $tenant->numero_personas_dependen);
                                            $set('otra_persona_aporta', $tenant->otra_persona_aporta);
                                            $set('numero_personas_aportan', $tenant->numero_personas_aportan);
                                            $set('persona_aporta_nombres', $tenant->persona_aporta_nombres);
                                            $set('persona_aporta_primer_apellido', $tenant->persona_aporta_primer_apellido);
                                            $set('persona_aporta_segundo_apellido', $tenant->persona_aporta_segundo_apellido);
                                            $set('persona_aporta_parentesco', $tenant->persona_aporta_parentesco);
                                            $set('persona_aporta_telefono', $tenant->persona_aporta_telefono);
                                            $set('persona_aporta_empresa', $tenant->persona_aporta_empresa);
                                            $set('persona_aporta_ingreso_comprobable', $tenant->persona_aporta_ingreso_comprobable);
                                            
                                            // Pre-llenar campos de uso de propiedad
                                            $set('tipo_inmueble_desea', $tenant->tipo_inmueble_desea);
                                            $set('giro_negocio', $tenant->giro_negocio);
                                            $set('experiencia_giro', $tenant->experiencia_giro);
                                            $set('propositos_arrendamiento', $tenant->propositos_arrendamiento);
                                            $set('sustituye_otro_domicilio', $tenant->sustituye_otro_domicilio);
                                            $set('domicilio_anterior_calle', $tenant->domicilio_anterior_calle);
                                            $set('domicilio_anterior_numero_exterior', $tenant->domicilio_anterior_numero_exterior);
                                            $set('domicilio_anterior_numero_interior', $tenant->domicilio_anterior_numero_interior);
                                            $set('domicilio_anterior_codigo_postal', $tenant->domicilio_anterior_codigo_postal);
                                            $set('domicilio_anterior_colonia', $tenant->domicilio_anterior_colonia);
                                            $set('domicilio_anterior_delegacion_municipio', $tenant->domicilio_anterior_delegacion_municipio);
                                            $set('domicilio_anterior_estado', $tenant->domicilio_anterior_estado);
                                            $set('motivo_cambio_domicilio', $tenant->motivo_cambio_domicilio);
                                        }
                                    }
                                }
                            }),

                        Forms\Components\Select::make('tipo_persona')
                            ->label('Tipo de Persona')
                            ->options([
                                'fisica' => 'Persona Física',
                                'moral' => 'Persona Moral',
                            ])
                            ->required()
                            ->live()
                            ->default('fisica')
                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                // Limpiar campos cuando cambia el tipo de persona si es necesario
                            }),

                        Forms\Components\Select::make('tipo_inmueble')
                            ->label('Tipo de Inmueble')
                            ->options([
                                'residencial' => 'Inmuebles Residenciales',
                                'comercial' => 'Inmuebles Comerciales',
                            ])
                            ->required()
                            ->live()
                            ->default('residencial'),

                        Forms\Components\Placeholder::make('folio')
                            ->label('Folio')
                            ->content(fn ($record) => $record?->folio ?? 'Se generará automáticamente')
                            ->visible(fn ($livewire) => $livewire instanceof Pages\EditApplications),

                        Forms\Components\Select::make('estatus')
                            ->label('Estatus')
                            ->options([
                                'pendiente' => 'Pendiente',
                                'en_revision' => 'En Revisión',
                                'activa' => 'Activa',
                                'aprobada' => 'Aprobada',
                                'rechazada' => 'Rechazada',
                                'vencida' => 'Vencida',
                            ])
                            ->required()
                            ->default('pendiente'),
                    ])
                    ->columns(2),

                // SECCIÓN: DATOS DE EMPLEO E INGRESOS (solo para persona física)
                Forms\Components\Section::make('Datos de Empleo e Ingresos')
                    ->description('Información sobre el empleo y situación económica')
                    ->schema(self::getDatosEmpleoSchema())
                    ->columns(2)
                    ->collapsible()
                    ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica'),

                // SECCIÓN: REFERENCIAS COMERCIALES (solo para persona moral)
                Forms\Components\Section::make('Referencias Comerciales')
                    ->description('Referencias comerciales de la empresa')
                    ->schema(self::getReferenciasComercialesSchema())
                    ->columns(2)
                    ->collapsible()
                    ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'moral'),

                // SECCIÓN: USO DE PROPIEDAD (solo para inmuebles comerciales)
                Forms\Components\Section::make('Uso de Propiedad')
                    ->description('Información sobre el uso que dará al inmueble')
                    ->schema(self::getUsoPropiedadSchema())
                    ->columns(2)
                    ->collapsible()
                    ->visible(fn (Forms\Get $get) => $get('tipo_inmueble') === 'comercial'),

                // SECCIÓN: DOCUMENTOS DEL INQUILINO
                Forms\Components\Section::make('Documentos del Inquilino')
                    ->description(fn (Forms\Get $get) => $get('tipo_persona') === 'moral' 
                        ? 'Documentos requeridos para Persona Moral' 
                        : 'Documentos requeridos para Persona Física')
                    ->schema([
                        Forms\Components\Placeholder::make('application_docs_list')
                            ->label('Documentos cargados')
                            ->content(function ($record, $livewire) {
                                if (!$record || !($livewire instanceof Pages\EditApplications)) {
                                    return 'Guarde la solicitud primero para poder cargar documentos';
                                }
                                
                                $docs = $record->documents ?? collect();
                                if ($docs->isEmpty()) return 'No hay documentos cargados';
                                
                                $tipoPersona = $record->tipo_persona ?? 'fisica';
                                $tipos = $tipoPersona === 'moral' 
                                    ? ApplicationDocument::tiposPersonaMoral() 
                                    : ApplicationDocument::tiposPersonaFisica();
                                
                                return new \Illuminate\Support\HtmlString(
                                    $docs->map(function ($doc) use ($tipos, $livewire) {
                                        $label = ($tipos[$doc->tag] ?? $doc->tag) . ' - ' . basename($doc->path_file);
                                        $url = Storage::disk('public')->url($doc->path_file);
                                        return "<div class='flex items-center gap-2 py-1'>
                                            <span class='flex-1'>{$label}</span>
                                            <a href='{$url}' target='_blank' class='text-blue-500 hover:text-blue-700' title='Ver'><svg class='w-5 h-5' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M15 12a3 3 0 11-6 0 3 3 0 016 0z'></path><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z'></path></svg></a>
                                            <a href='{$url}' download class='text-green-500 hover:text-green-700' title='Descargar'><svg class='w-5 h-5' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4'></path></svg></a>
                                            <button type='button' wire:click=\"deleteApplicationDocument({$doc->id})\" class='text-red-500 hover:text-red-700' title='Eliminar'><svg class='w-5 h-5' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16'></path></svg></button>
                                        </div>";
                                    })->implode('')
                                );
                            })
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->visible(fn (Forms\Get $get) => $get('tipo_persona') !== null),

                // Acción para subir documentos (solo en edición)
                Forms\Components\Actions::make([
                    Forms\Components\Actions\Action::make('subir_documento')
                        ->label('Subir Documento')
                        ->color('primary')
                        ->icon('heroicon-o-arrow-up-tray')
                        ->form([
                            Forms\Components\Select::make('tag')
                                ->label('Tipo de Documento')
                                ->options(function (Forms\Get $get, $livewire) {
                                    $tipoPersona = $get('tipo_persona');
                                    if (!$tipoPersona && $livewire instanceof Pages\EditApplications) {
                                        $tipoPersona = $livewire->record->tipo_persona ?? 'fisica';
                                    }
                                    return ($tipoPersona === 'moral') 
                                        ? ApplicationDocument::tiposPersonaMoral() 
                                        : ApplicationDocument::tiposPersonaFisica();
                                })
                                ->required(),
                            Forms\Components\FileUpload::make('file')
                                ->label('Archivo')
                                ->directory('application-documents')
                                ->acceptedFileTypes(['application/pdf', 'image/*'])
                                ->maxSize(5120)
                                ->required(),
                        ])
                        ->action(function (array $data, $livewire) {
                            if (!($livewire instanceof Pages\EditApplications) || !$livewire->record) {
                                \Filament\Notifications\Notification::make()
                                    ->danger()
                                    ->title('Error')
                                    ->body('Debe guardar la solicitud primero')
                                    ->send();
                                return;
                            }
                            
                            ApplicationDocument::create([
                                'application_id' => $livewire->record->id,
                                'user_id' => auth()->id(),
                                'user_name' => auth()->user()->name,
                                'tag' => $data['tag'],
                                'path_file' => $data['file'],
                                'mime' => Storage::disk('public')->mimeType($data['file']) ?? 'application/octet-stream',
                            ]);
                            
                            // Recargar la relación de documentos
                            $livewire->record->load('documents');
                            
                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Documento subido correctamente')
                                ->send();
                        })
                        ->visible(fn ($livewire) => $livewire instanceof Pages\EditApplications),
                ])
                ->visible(fn (Forms\Get $get, $livewire) => $livewire instanceof Pages\EditApplications && $get('tipo_persona') !== null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('folio')
                    ->label('Folio')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Folio copiado')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Tenant')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('estatus')
                    ->label('Estatus')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pendiente' => 'warning',
                        'en_revision' => 'info',
                        'activa' => 'success',
                        'aprobada' => 'success',
                        'rechazada' => 'danger',
                        'vencida' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pendiente' => 'Pendiente',
                        'en_revision' => 'En Revisión',
                        'activa' => 'Activa',
                        'aprobada' => 'Aprobada',
                        'rechazada' => 'Rechazada',
                        'vencida' => 'Vencida',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de Creación')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Tenant')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('estatus')
                    ->label('Estatus')
                    ->options([
                        'pendiente' => 'Pendiente',
                        'en_revision' => 'En Revisión',
                        'activa' => 'Activa',
                        'aprobada' => 'Aprobada',
                        'rechazada' => 'Rechazada',
                        'vencida' => 'Vencida',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->iconButton() // Convierte el botón a solo icono
                    ->tooltip('Editar'),
                Tables\Actions\ViewAction::make()
                    ->iconButton() // Convierte el botón a solo icono
                    ->tooltip('Ver'),
            ])
            ->actionsColumnLabel('ACCIONES')
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApplications::route('/'),
            'edit' => Pages\EditApplications::route('/{record}/edit'),
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
                ->columnSpanFull(),

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
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ];
    }

    protected static function getUsoPropiedadSchema(): array
    {
        return [
            Forms\Components\Group::make()
                ->schema([
                    Forms\Components\Placeholder::make('uso_comercial_info')
                        ->label('Datos del uso comercial')
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
                        ->helperText('Establecer sucursal, oficina matriz, domicilio fiscal, centro de operaciones, almacén, punto de venta, etc.')
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

                    // Información del domicilio anterior
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
                                ->label('Nombre de la empresa')
                                ->required(),

                            Forms\Components\TextInput::make('referencia_comercial1_contacto')
                                ->label('Nombre del contacto')
                                ->required(),

                            Forms\Components\TextInput::make('referencia_comercial1_telefono')
                                ->label('Teléfono')
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
                                ->label('Nombre de la empresa')
                                ->required(),

                            Forms\Components\TextInput::make('referencia_comercial2_contacto')
                                ->label('Nombre del contacto')
                                ->required(),

                            Forms\Components\TextInput::make('referencia_comercial2_telefono')
                                ->label('Teléfono')
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
                                ->label('Nombre de la empresa')
                                ->required(),

                            Forms\Components\TextInput::make('referencia_comercial3_contacto')
                                ->label('Nombre del contacto')
                                ->required(),

                            Forms\Components\TextInput::make('referencia_comercial3_telefono')
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
}
