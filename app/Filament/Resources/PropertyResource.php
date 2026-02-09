<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PropertyResource\Pages;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Models\Owner;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class PropertyResource extends Resource
{
    protected static ?string $model = Property::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationLabel = 'Propiedades';
    protected static ?string $navigationGroup = 'Rentas';
    protected static ?int $navigationSort = 4;
    protected static ?string $modelLabel = 'Propiedad';
    protected static ?string $pluralModelLabel = 'Propiedades';

    public static function getCluster(): ?string
    {
        return null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información de la Propiedad')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Propietario')
                            ->relationship('user', 'name', modifyQueryUsing: fn (Builder $query) => $query->where('is_owner', true))
                            ->getOptionLabelFromRecordUsing(fn (User $record) => $record->name . ' (' . $record->email . ')')
                            ->searchable(['name', 'email'])
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                if ($state) {
                                    $user = User::find($state);
                                    if ($user && $user->is_owner) {
                                        $owner = Owner::where('user_id', $user->id)->first();
                                        if ($owner) {
                                            // Pre-llenar campos de inmueble desde Owner si existen
                                            // Por ahora no hay campos de inmueble en Owner, pero se puede agregar después
                                        }
                                    }
                                }
                            }),

                        Forms\Components\Placeholder::make('folio')
                            ->label('Folio')
                            ->content(fn ($record) => $record?->folio ?? 'Se generará automáticamente')
                            ->visible(fn ($livewire) => $livewire instanceof Pages\EditProperty),

                        Forms\Components\Select::make('estatus')
                            ->label('Estatus')
                            ->options([
                                'disponible' => 'Disponible',
                                'rentada' => 'Rentada',
                                'inactiva' => 'Inactiva',
                            ])
                            ->required()
                            ->default('disponible'),
                    ])
                    ->columns(2),

                // SECCIÓN: DATOS DEL INMUEBLE
                Forms\Components\Section::make('Datos del Inmueble')
                    ->description('Información acerca del inmueble')
                    ->schema(self::getDatosInmuebleSchema())
                    ->columns(2)
                    ->collapsible(),

                // SECCIÓN: DIRECCIÓN DEL INMUEBLE
                Forms\Components\Section::make('Dirección del Inmueble')
                    ->schema(self::getDireccionInmuebleSchema())
                    ->columns(2)
                    ->collapsible(),

                // SECCIÓN: IMÁGENES DE LA PROPIEDAD
                Forms\Components\Section::make('Imágenes de la Propiedad')
                    ->description('Cargue las imágenes de la propiedad. Seleccione una como portada.')
                    ->schema([
                        Forms\Components\Placeholder::make('property_images_list')
                        ->label('')
                        ->content(function ($record, $livewire) {
                            // Validaciones iniciales
                            if (!$record || !($livewire instanceof Pages\EditProperty)) {
                                return new \Illuminate\Support\HtmlString(
                                    '<div class="p-4 text-sm text-yellow-800 rounded-lg bg-yellow-50 dark:bg-gray-800 dark:text-yellow-300">
                                        <span class="font-medium">Atención:</span> Guarde la propiedad primero para cargar imágenes.
                                    </div>'
                                );
                            }
                            
                            $images = $record->images()->orderBy('is_portada', 'desc')->orderBy('order')->get();
                            
                            if ($images->isEmpty()) {
                                return new \Illuminate\Support\HtmlString('<div class="text-center text-gray-500 py-4">No hay imágenes cargadas aún.</div>');
                            }
                            
                            $html = '<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">';
                            
                            foreach ($images as $image) {
                                $url = Storage::disk('public')->url($image->path_file);
                                $isPortada = $image->is_portada;
                                
                                // Estilos dinámicos según si es portada
                                $borderColor = $isPortada ? 'border-success-500 ring-2 ring-success-500' : 'border-gray-200 dark:border-gray-700';
                                $badge = $isPortada ? '<div class="absolute top-2 right-2 bg-success-500 text-white text-xs font-bold px-2 py-1 rounded shadow-sm">Portada</div>' : '';
                                
                                // Botones de acción
                                $btnPortada = $isPortada 
                                    ? '<button disabled class="w-full text-xs py-1.5 rounded bg-gray-100 text-gray-400 cursor-not-allowed font-medium dark:bg-gray-700">Es Portada</button>'
                                    : '<button wire:click="setPortada('.$image->id.')" class="w-full text-xs py-1.5 rounded bg-primary-600 text-white hover:bg-primary-500 transition font-medium">★ Hacer Portada</button>';
                                
                                $btnEliminar = '<button wire:click="deletePropertyImage('.$image->id.')" class="text-danger-500 hover:text-danger-600 p-1" title="Eliminar">
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
                    ])
                    ->collapsible()
                    ->visible(fn ($livewire) => $livewire instanceof Pages\EditProperty),

                // Acción para subir imágenes (solo en edición)
                Forms\Components\Actions::make([
                    Forms\Components\Actions\Action::make('subir_imagen')
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
                        ->action(function (array $data, $livewire) {
                            if (!($livewire instanceof Pages\EditProperty) || !$livewire->record) {
                                \Filament\Notifications\Notification::make()
                                    ->danger()
                                    ->title('Error')
                                    ->body('Debe guardar la propiedad primero')
                                    ->send();
                                return;
                            }
                            
                            PropertyImage::create([
                                'property_id' => $livewire->record->id,
                                'user_id' => auth()->id(),
                                'user_name' => auth()->user()->name,
                                'path_file' => $data['file'],
                                'is_portada' => $livewire->record->images()->count() === 0, // Primera imagen es portada por defecto
                            ]);
                            
                            // Recargar la relación de imágenes
                            $livewire->record->load('images');
                            
                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Imagen subida correctamente')
                                ->send();
                        })
                        ->visible(fn ($livewire) => $livewire instanceof Pages\EditProperty),
                ])
                ->visible(fn ($livewire) => $livewire instanceof Pages\EditProperty),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('portada')
                    ->label('') // Sin etiqueta para ahorrar espacio
                    ->state(function ($record) {
                        // Busca la imagen marcada como portada, si no, usa la primera
                        $img = $record->images->where('is_portada', true)->first() 
                            ?? $record->images->first();
                        return $img ? $img->path_file : null;
                    })
                    ->disk('public') // Asegúrate de que coincida con tu filesystem
                    ->square()
                    ->size(50)
                    ->extraImgAttributes([
                        'class' => 'object-cover rounded-md transition-transform duration-300 ease-in-out hover:scale-[2.5] hover:z-50 hover:shadow-xl border border-gray-200 cursor-zoom-in origin-left',
                        'style' => 'z-index: 10;', // Fuerza que quede encima al hacer zoom
                        'loading' => 'lazy',
                    ]),

                Tables\Columns\TextColumn::make('folio')
                    ->label('Folio')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Folio copiado')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Propietario')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('tipo_inmueble')
                    ->label('Tipo')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('calle')
                    ->label('Dirección')
                    ->formatStateUsing(fn ($record) => 
                        ($record->calle ?? '') . ' ' . 
                        ($record->numero_exterior ?? '') . ' ' . 
                        ($record->colonia ?? '')
                    )
                    ->searchable(['calle', 'colonia'])
                    ->limit(30),

                Tables\Columns\TextColumn::make('precio_renta')
                    ->label('Precio Renta')
                    ->money('MXN')
                    ->sortable(),

                Tables\Columns\TextColumn::make('estatus')
                    ->label('Estatus')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'disponible' => 'success',
                        'rentada' => 'warning',
                        'inactiva' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'disponible' => 'Disponible',
                        'rentada' => 'Rentada',
                        'inactiva' => 'Inactiva',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de Creación')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Propietario')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('tipo_inmueble')
                    ->label('Tipo de Inmueble')
                    ->options([
                        'Casa' => 'Casa',
                        'Departamento' => 'Departamento',
                        'Local comercial' => 'Local comercial',
                        'Oficina' => 'Oficina',
                        'Bodega' => 'Bodega',
                        'Nave industrial' => 'Nave industrial',
                        'Consultorio' => 'Consultorio',
                        'Terreno' => 'Terreno',
                    ]),

                Tables\Filters\SelectFilter::make('estatus')
                    ->label('Estatus')
                    ->options([
                        'disponible' => 'Disponible',
                        'rentada' => 'Rentada',
                        'inactiva' => 'Inactiva',
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
            ->defaultSort('created_at', 'desc')
            ->recordClasses(fn ($record) => 'h-34');
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
            'index' => Pages\ListProperties::route('/'),
            'create' => Pages\CreateProperty::route('/create'),
            'edit' => Pages\EditProperty::route('/{record}/edit'),
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

            Forms\Components\Textarea::make('inventario')
                ->label('Inventario del inmueble')
                ->rows(3)
                ->required()
                ->columnSpanFull()
                ->helperText('Describa el inventario con el que cuenta el inmueble, por ejemplo, cortinas, muebles, hidroneumático, etc.'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user->hasRole('Administrador')) {
            return $query;
        }

        if ($user->hasRole('Asesor')) {
            // Filtrar propiedades donde el Propietario (Owner) esté asignado a este Asesor
            // Asumiendo que Property tiene 'user_id' (el dueño) 
            // y Owner tiene 'user_id' y 'asesor_id'.
            
            return $query->whereHas('user', function ($q) use ($user) {
                // Buscamos en la tabla users -> owners
                $q->whereHas('owner', function ($q2) use ($user) {
                    $q2->where('asesor_id', $user->id);
                });
            });
        }

        return $query;
    }
}
