<?php

namespace App\Livewire;

use App\Models\Rent;
use App\Models\PaymentSetting;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Livewire\Component;
use Illuminate\Support\HtmlString;

class SettingsManager extends Component implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    public $rentId;
    public ?array $data = []; // Datos del formulario de notificaciones

    public function mount($rentId)
    {
        $this->rentId = $rentId;
        $record = Rent::findOrFail($rentId);
        $this->form->fill($record->attributesToArray());
    }

    // --- (Agente y Notificaciones) ---
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // 1. SECCIÓN AGENTE 
                Forms\Components\Group::make()->schema([
                    Forms\Components\Grid::make(12)->schema([
                        Forms\Components\Placeholder::make('agente_info')
                            ->hiddenLabel()
                            ->columnSpan(['default' => 9, 'md' => 10])
                            ->content(function () {
                                $record = Rent::find($this->rentId);
                                $user = $record->asesor ?? auth()->user();
                                return new HtmlString("
                                    <div class='flex flex-col justify-center h-full'>
                                        <h3 class='text-gray-500 font-medium text-sm'>Administración de la Renta</h3>
                                        <div class='mt-1'>
                                            <p class='text-sm font-bold text-gray-800 dark:text-gray-300'>Agente Rentas.com:</p>
                                            <p class='text-xl font-extrabold text-black dark:text-white'>{$user->name}</p>
                                        </div>
                                    </div>
                                ");
                            }),
                        Forms\Components\Placeholder::make('agente_avatar')
                            ->hiddenLabel()
                            ->columnSpan(['default' => 3, 'md' => 2])
                            ->content(function () {
                                $record = Rent::find($this->rentId);
                                $user = $record->asesor ?? auth()->user();
                                $avatar = $user->profile_photo_url ?? 'https://ui-avatars.com/api/?name='.urlencode($user->name);
                                return new HtmlString("<div class='flex justify-end'><img src='{$avatar}' class='w-16 h-16 rounded-full object-cover shadow-sm'></div>");
                            }),
                    ]),
                ])->extraAttributes(['class' => 'bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-100 shadow-sm mb-6']),

                // 2. SECCIÓN NOTIFICACIONES 
                Forms\Components\Section::make('Notificaciones')
                ->extraAttributes(['class' => 'shadow-sm border-gray-100'])
                ->schema([
                    Forms\Components\Toggle::make('notif_desactivar_todas')
                        ->label('Desactivar todas las notificaciones')
                        ->onColor('danger')
                        ->reactive()
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            if ($state === true) {
                                $this->desactivarTodas($set);
                            }
                        }),

                    Forms\Components\Group::make()
                        ->schema([
                            Forms\Components\Toggle::make('notif_recordatorios')
                                ->label('Recordatorios de pago')
                                ->default(true)
                                ->onColor('warning'),

                            Forms\Components\Toggle::make('notif_reporte_pago')
                                ->label('Reporte de pago')
                                ->default(true)
                                ->onColor('warning'),

                            Forms\Components\Toggle::make('notif_mensajes')
                                ->label('Mensajes')
                                ->default(true)
                                ->onColor('warning'),

                            Forms\Components\Toggle::make('notif_mantenimiento')
                                ->label('Reporte de mantenimiento')
                                ->default(true)
                                ->onColor('warning'),
                        ])
                        ->disabled(fn (Forms\Get $get) => $get('notif_desactivar_todas'))
                        ->columns(1),
                ]),
            ])
            ->statePath('data');
    }

    public function desactivarTodas($set)
    {
        $set('notif_recordatorios', false);
        $set('notif_reporte_pago', false);
        $set('notif_mensajes', false);
        $set('notif_mantenimiento', false);
    }

    // Guardar Notificaciones (se llama automáticamente o con wire:change)
    public function updatedData()
    {
        Rent::find($this->rentId)->update($this->data);
        // Notification::make()->title('Preferencias guardadas')->success()->send();
    }

    // --- ACCIÓN: CREAR NUEVO PAGO (MODAL) ---
    public function createPaymentAction(): Action
    {
        return Action::make('createPayment')
            ->label('Crear nuevo pago')
            ->color('warning')
            ->button()
            ->modalHeading('Crear nuevo pago')
            ->modalWidth('sm')
            ->modalSubmitActionLabel('Guardar')
            // Formulario del Modal 
            ->form([
                Forms\Components\Select::make('tipo')
                    ->label('Tipo:')
                    ->options(['Agua'=>'Agua', 'Luz'=>'Luz', 'Gas'=>'Gas', 'Renta'=>'Renta', 'Mantenimiento'=>'Mantenimiento'])
                    ->default('Agua')
                    ->required(),
                
                Forms\Components\Select::make('frecuencia')
                    ->label('Frecuencia')
                    ->options(['Mensual'=>'Mensual', 'Bimestral'=>'Bimestral', 'Semanal'=>'Semanal'])
                    ->default('Bimestral')
                    ->required(),

                Forms\Components\Grid::make(3)->schema([
                    Forms\Components\TextInput::make('monto')
                        ->label('Monto')
                        ->placeholder('$$$$$$')
                        ->numeric()
                        ->columnSpan(2),
                    Forms\Components\Select::make('moneda')
                        ->label('')
                        ->options(['MXN'=>'MXN', 'USD'=>'USD'])
                        ->default('MXN')
                        ->columnSpan(1),
                ]),

                Forms\Components\Radio::make('es_variable')
                    ->label('El pago es variable')
                    ->boolean()
                    ->options([true => 'Si', false => 'No'])
                    ->inline()
                    ->default(true),

                Forms\Components\Select::make('recordatorio')
                    ->label('Recordatorio')
                    ->options([
                        'dia_pago' => 'El día de pago',
                        '5_dias' => '5 días antes',
                        '10_dias' => '10 días antes',
                    ])
                    ->default('dia_pago'),
                
                // Botón falso visual "+ Agregar recordatorio"
                Forms\Components\Placeholder::make('btn_add_rec')
                    ->hiddenLabel()
                    ->content(new HtmlString('<button type="button" class="w-full py-2 btn-figma-outline text-sm">+ Agregar recordatorio</button>')),
            ])
            ->action(function (array $data) {
                PaymentSetting::create([
                    'rent_id' => $this->rentId,
                    'tipo' => $data['tipo'],
                    'frecuencia' => $data['frecuencia'],
                    'monto' => $data['monto'],
                    'moneda' => $data['moneda'],
                    'es_variable' => $data['es_variable'],
                    'recordatorio' => $data['recordatorio'],
                    'activo' => true,
                ]);
                Notification::make()->title('Pago configurado')->success()->send();
            });
    }

    // Toggle para activar/desactivar un pago desde la lista
    public function togglePayment($paymentId)
    {
        $payment = PaymentSetting::find($paymentId);
        if($payment) {
            $payment->activo = !$payment->activo;
            $payment->save();
        }
    }

    public function render()
    {
        return view('livewire.settings-manager', [
            'paymentSettings' => PaymentSetting::where('rent_id', $this->rentId)->get()
        ]);
    }
}