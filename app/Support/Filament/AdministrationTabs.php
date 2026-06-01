<?php

declare(strict_types=1);

namespace App\Support\Filament;

use App\Livewire\ChatManager;
use App\Livewire\MaintenanceManager;
use App\Livewire\PaymentManager;
use App\Livewire\SettingsManager;
use App\Models\Message;
use App\Models\Rent;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class AdministrationTabs
{
    public static function make(Rent $record): Forms\Components\Tabs
    {
        return Forms\Components\Tabs::make('AdministracionTabs')
            ->columnSpanFull()
            ->tabs([
                Forms\Components\Tabs\Tab::make('Información general')
                    ->icon('heroicon-o-home')
                    ->schema([
                        Forms\Components\Placeholder::make('imagen_central')
                            ->hiddenLabel()
                            ->columnSpanFull()
                            ->content(function () use ($record) {
                                $property = $record->property;
                                $img = $property?->images?->where('is_portada', true)->first() ?? $property?->images?->first();
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

                        Forms\Components\Section::make('Información General')
                            ->schema([
                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('tipo_propiedad_view')
                                        ->label('Tipo de Propiedad')
                                        ->formatStateUsing(fn () => $record->property?->tipo_inmueble)
                                        ->disabled()
                                        ->dehydrated(false),

                                    Forms\Components\TextInput::make('direccion_view')
                                        ->label('Dirección')
                                        ->formatStateUsing(fn () => trim(($record->property?->calle ?? '').' '.($record->property?->numero_exterior ?? '').', '.($record->property?->colonia ?? '')))
                                        ->disabled()
                                        ->dehydrated(false),
                                ]),

                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\DatePicker::make('start_date')
                                        ->label('Fecha de inicio de la renta')
                                        ->disabled()
                                        ->dehydrated(false),

                                    Forms\Components\DatePicker::make('end_date')
                                        ->label('Fecha de término')
                                        ->disabled()
                                        ->dehydrated(false),
                                ]),

                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('monto_renta_view')
                                        ->label('Monto de renta')
                                        ->prefix('$')
                                        ->formatStateUsing(fn () => number_format((float) ($record->monto ?? 0), 2))
                                        ->disabled()
                                        ->dehydrated(false),

                                    Forms\Components\TextInput::make('monto_mantenimiento_view')
                                        ->label('Monto de mantenimiento')
                                        ->prefix('$')
                                        ->formatStateUsing(fn () => number_format((float) ($record->property?->costo_mantenimiento_mensual ?? 0), 2))
                                        ->disabled()
                                        ->dehydrated(false),
                                ]),

                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('frecuencia_pago_view')
                                        ->label('Frecuencia de pago')
                                        ->formatStateUsing(fn () => $record->property?->frecuencia_pago)
                                        ->disabled()
                                        ->dehydrated(false),

                                    Forms\Components\TextInput::make('dia_pago_view')
                                        ->label('Fecha de pago (Día)')
                                        ->default('Día 5 de cada mes')
                                        ->disabled()
                                        ->dehydrated(false),
                                ]),
                            ]),

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
                                ->action(function (): void {
                                    Notification::make()
                                        ->title('Incidencia reportada')
                                        ->warning()
                                        ->send();
                                }),
                        ])
                            ->alignCenter()
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Tabs\Tab::make('Configuración')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->schema([
                        Forms\Components\Livewire::make(SettingsManager::class, ['rentId' => $record->id])
                            ->key('settings-manager-'.$record->id),
                    ]),

                Forms\Components\Tabs\Tab::make('Reportes de pago')
                    ->icon('heroicon-o-currency-dollar')
                    ->schema([
                        Forms\Components\Livewire::make(PaymentManager::class, ['rentId' => $record->id])
                            ->key('payment-manager-'.$record->id),
                    ]),

                Forms\Components\Tabs\Tab::make('Mantenimiento')
                    ->icon('heroicon-o-wrench')
                    ->schema([
                        Forms\Components\Livewire::make(MaintenanceManager::class, ['rentId' => $record->id])
                            ->key('mantenimiento-'.$record->id),
                    ]),

                Forms\Components\Tabs\Tab::make('Mensajes')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->badge(function () use ($record) {
                        $userId = auth()->id();
                        if (! $userId) {
                            return null;
                        }

                        $count = Message::where('rent_id', $record->id)
                            ->where('user_id', '!=', $userId)
                            ->where('visto', false)
                            ->count();

                        return $count > 0 ? $count : null;
                    })
                    ->badgeColor('warning')
                    ->schema([
                        Forms\Components\Livewire::make(ChatManager::class, ['rentId' => $record->id])
                            ->key('chat-manager-'.$record->id),
                    ]),
            ]);
    }
}
