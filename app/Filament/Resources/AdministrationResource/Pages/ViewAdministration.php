<?php

namespace App\Filament\Resources\AdministrationResource\Pages;

use App\Filament\Resources\AdministrationResource;
use App\Models\Rent;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Actions;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Storage;

class ViewAdministration extends EditRecord
{
    protected static string $resource = AdministrationResource::class;
    
    protected static ?string $title = 'Administración de Inmueble';

    // Ocultamos los botones globales de "Guardar" del pie de página
    protected function getFormActions(): array
    {
        return [];
    }

    public function resolveRecord(string|int $key): Model
    {
        $record = Rent::findByHash($key);

        if (!$record) {
            abort(404);
        }

        return $record;
    }

    // Botón para volver al listado
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('volver')
                ->label('Volver al listado')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn () => AdministrationResource::getUrl('index')),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('AdministracionTabs')
                    ->columnSpanFull() // Ocupa todo el ancho
                    ->tabs([
                        
                        // --- 1. DATOS DE LA PROPIEDAD ---
                        Forms\Components\Tabs\Tab::make('Datos de la propiedad')
                            ->icon('heroicon-o-home')
                            ->schema([
                                
                                // --- IMAGEN CENTRAL ---
                                Forms\Components\Placeholder::make('imagen_central')
                                    ->hiddenLabel()
                                    ->columnSpanFull()
                                    ->content(function ($record) {
                                        $property = $record->property;
                                        $img = $property->images->where('is_portada', true)->first() ?? $property->images->first();
                                        $url = $img ? Storage::disk('public')->url($img->path_file) : null;
                                        
                                        if ($url) {
                                            return new HtmlString("
                                                <div class='flex justify-center mb-6'>
                                                    <div class='w-full md:w-1/2 h-64 rounded-xl overflow-hidden shadow-lg border border-gray-200'>
                                                        <img src='{$url}' class='w-full h-full object-cover'>
                                                    </div>
                                                </div>
                                            ");
                                        }
                                        return new HtmlString("<div class='h-64 w-full bg-gray-100 rounded-xl flex items-center justify-center text-gray-400 border border-gray-200 mb-6'>Sin Imagen</div>");
                                    }),

                                // --- DATOS PRINCIPALES ---
                                Forms\Components\Section::make('Información General')
                                    ->schema([
                                        Forms\Components\Grid::make(2)->schema([
                                            Forms\Components\TextInput::make('tipo_propiedad_view')
                                                ->label('Tipo de Propiedad')
                                                ->formatStateUsing(fn ($record) => $record->property->tipo_inmueble)
                                                ->disabled()->dehydrated(false),
                                                
                                            Forms\Components\TextInput::make('direccion_view')
                                                ->label('Dirección')
                                                ->formatStateUsing(fn ($record) => trim(($record->property->calle ?? '') . ' ' . ($record->property->numero_exterior ?? '') . ', ' . ($record->property->colonia ?? '')))
                                                ->disabled()->dehydrated(false),
                                        ]),

                                        // FECHAS
                                        Forms\Components\Grid::make(2)->schema([
                                            Forms\Components\DatePicker::make('fecha_inicio')
                                                ->label('Fecha de inicio de la renta')
                                                ->disabled()->dehydrated(false),
                                            
                                            Forms\Components\DatePicker::make('fecha_fin')
                                                ->label('Fecha de término')
                                                ->disabled()->dehydrated(false),
                                        ]),

                                        // MONTOS
                                        Forms\Components\Grid::make(2)->schema([
                                            Forms\Components\TextInput::make('monto_renta_view')
                                                ->label('Monto de renta')
                                                ->prefix('$')
                                                ->formatStateUsing(fn ($record) => number_format($record->monto ?? 0, 2))
                                                ->disabled()->dehydrated(false),

                                            Forms\Components\TextInput::make('monto_mantenimiento_view')
                                                ->label('Monto de mantenimiento')
                                                ->prefix('$')
                                                ->formatStateUsing(fn ($record) => number_format($record->property->costo_mantenimiento_mensual ?? 0, 2))
                                                ->disabled()->dehydrated(false),
                                        ]),

                                        // FRECUENCIA Y FECHA PAGO
                                        Forms\Components\Grid::make(2)->schema([
                                            Forms\Components\TextInput::make('frecuencia_pago_view')
                                                ->label('Frecuencia de pago')
                                                ->formatStateUsing(fn ($record) => $record->property->frecuencia_pago)
                                                ->disabled()->dehydrated(false),

                                            Forms\Components\TextInput::make('dia_pago_view')
                                                ->label('Fecha de pago (Día)')
                                                ->default('Día 5 de cada mes') 
                                                ->disabled()->dehydrated(false),
                                        ]),
                                    ]),

                                // --- SERVICIOS INCLUIDOS ---
                                Forms\Components\Section::make('Servicios Incluidos')
                                    ->compact()
                                    ->schema([
                                        Forms\Components\Textarea::make('servicios_view')
                                            ->hiddenLabel()
                                            ->formatStateUsing(fn ($record) => $record->property->servicios_pagar)
                                            ->disabled()->dehydrated(false)
                                            ->rows(2),
                                    ]),

                                // --- HISTORIALES (PLACEHOLDERS) ---                                
                                Forms\Components\Section::make('Historial de pagos de la renta')
                                    ->collapsible()
                                    ->collapsed()
                                    ->schema([
                                        Forms\Components\Placeholder::make('historial_renta')
                                            ->hiddenLabel()
                                            ->content(new HtmlString('<div class="p-4 text-center text-sm text-gray-500 bg-gray-50 rounded border border-dashed">Tabla de pagos de renta...</div>')),
                                    ]),

                                Forms\Components\Section::make('Historial de pagos de agua')
                                    ->collapsible()
                                    ->collapsed()
                                    ->schema([
                                        Forms\Components\Placeholder::make('historial_agua')
                                            ->hiddenLabel()
                                            ->content(new HtmlString('<div class="p-4 text-center text-sm text-gray-500 bg-gray-50 rounded border border-dashed">Tabla de pagos de agua...</div>')),
                                    ]),

                                Forms\Components\Section::make('Historial de mantenimiento')
                                    ->collapsible()
                                    ->collapsed()
                                    ->schema([
                                        Forms\Components\Placeholder::make('historial_manto')
                                            ->hiddenLabel()
                                            ->content(new HtmlString('<div class="p-4 text-center text-sm text-gray-500 bg-gray-50 rounded border border-dashed">Tabla de mantenimientos...</div>')),
                                    ]),

                                // --- BOTÓN REPORTAR INCIDENCIA ---
                                Forms\Components\Actions::make([
                                    Forms\Components\Actions\Action::make('reportar_incidencia_tab1')
                                        ->label('Reportar incidencia')
                                        ->color('warning') 
                                        ->icon('heroicon-m-exclamation-triangle')
                                        ->button()
                                        ->form([
                                            Forms\Components\Textarea::make('descripcion')
                                                ->label('Detalle del problema')
                                                ->required(),
                                            Forms\Components\FileUpload::make('evidencia')
                                                ->label('Foto / Evidencia')
                                                ->image()
                                                ->directory('incidencias'),
                                        ])
                                        ->action(function (array $data) {
                                            Notification::make()
                                                ->title('Incidencia reportada')
                                                ->warning()
                                                ->send();
                                        }),
                                ])
                                ->alignCenter() 
                                ->columnSpanFull(), 
                            ]),

                        // --- 2. CONFIGURACIÓN ---
                        Forms\Components\Tabs\Tab::make('Configuración')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                // Aquí insertamos el componente Livewire
                                Forms\Components\Livewire::make(\App\Livewire\SettingsManager::class, ['rentId' => $this->record->id])
                                    ->key('settings-manager-' . $this->record->id),
                            ]),

                        // --- 3. REPORTES DE PAGO ---
                        Forms\Components\Tabs\Tab::make('Reportes de pago')
                            ->icon('heroicon-o-currency-dollar')
                            ->schema([
                                // Aquí insertamos el componente Livewire
                                Forms\Components\Livewire::make(\App\Livewire\PaymentManager::class, ['rentId' => $this->record->id])
                                    ->key('payment-manager-' . $this->record->id),
                            ]),

                        // --- 4. MANTENIMIENTO ---
                        Forms\Components\Tabs\Tab::make('Mantenimiento')
                            ->icon('heroicon-o-wrench')
                            ->schema([
                                // Aquí llamamos al componente pasando el ID de la renta
                                Forms\Components\Livewire::make(\App\Livewire\MaintenanceManager::class, ['rentId' => $this->record->id])
                                    ->key('mantenimiento-' . $this->record->id),
                            ]),

                        // --- 5. MENSAJES ---
                        Forms\Components\Tabs\Tab::make('Mensajes')
                            ->icon('heroicon-o-chat-bubble-left-right')
                            ->schema([
                                // Aquí llamamos al componente pasando el ID de la renta
                                Forms\Components\Livewire::make(\App\Livewire\ChatManager::class, ['rentId' => $this->record->id])
                                    ->key('chat-manager-' . $this->record->id),
                            ]),
                    ]),
            ]);
    }
}