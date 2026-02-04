<?php

namespace App\Livewire;

use App\Models\Service;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms;
use Filament\Notifications\Notification;
use Livewire\Component;
use Illuminate\Support\Carbon;

class PaymentManager extends Component implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    public $rentId;

    public function mount($rentId)
    {
        $this->rentId = $rentId;
    }

    public function render()
    {
        $servicios = Service::where('rent_id', $this->rentId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('mes_correspondiente');

        return view('livewire.payment-manager', [
            'serviciosPorMes' => $servicios
        ]);
    }

    // Acción de CREAR
    public function reportarPagoAction(): Action
    {
        return Action::make('reportarPago')
            ->label('Reportar Pago')
            ->color('warning')
            ->icon('heroicon-m-plus')
            ->modalHeading('Registrar nuevo pago')
            ->modalWidth('md')
            ->form([
                Forms\Components\ToggleButtons::make('tipo') 
                    ->hiddenLabel()
                    ->options([
                        'gas' => 'GAS',
                        'agua' => 'AGUA',
                        'luz' => 'LUZ',
                        'renta' => 'RENTA',
                        'mantenimiento' => 'MANTENIMIENTO',
                    ])
                    ->colors([
                        'gas' => 'success',
                        'agua' => 'info',
                        'luz' => 'warning',
                        'renta' => 'primary',
                        'mantenimiento' => 'gray',
                    ])
                    ->icons([
                        'gas' => 'heroicon-o-fire',
                        'agua' => 'heroicon-o-beaker',
                        'luz' => 'heroicon-o-bolt',
                        'renta' => 'heroicon-o-home',
                        'mantenimiento' => 'heroicon-o-wrench',
                    ])
                    ->inline()->required()->columnSpanFull(),

                Forms\Components\Group::make()->schema([
                    Forms\Components\Select::make('mes_correspondiente')
                        ->options([
                            'Enero' => 'Enero', 'Febrero' => 'Febrero', 'Marzo' => 'Marzo',
                            'Abril' => 'Abril', 'Mayo' => 'Mayo', 'Junio' => 'Junio',
                            'Julio' => 'Julio', 'Agosto' => 'Agosto', 'Septiembre' => 'Septiembre',
                            'Octubre' => 'Octubre', 'Noviembre' => 'Noviembre', 'Diciembre' => 'Diciembre',
                        ])
                        ->default(Carbon::now()->translatedFormat('F'))->required(),

                    Forms\Components\DatePicker::make('fecha_pago')
                        ->default(now())->required(),

                    Forms\Components\Select::make('forma_pago')
                        ->options(['efectivo' => 'Efectivo', 'tarjeta' => 'Tarjeta', 'transferencia' => 'Transferencia'])
                        ->default('efectivo')->required(),

                    Forms\Components\TextInput::make('monto')->numeric()->prefix('$')->required(),
                ])->columns(1),

                Forms\Components\FileUpload::make('evidencia')->image()->directory('comprobantes')->columnSpanFull(),
                Forms\Components\Textarea::make('observaciones')->rows(2)->columnSpanFull(),
            ])
            ->action(function (array $data) {
                // Lógica simple para calcular estatus: Si es después del día 5, es "atrasado"
                $diaPago = Carbon::parse($data['fecha_pago'])->day;
                $estatus = $diaPago > 5 ? 'atrasado' : 'pagado';

                Service::create([
                    'rent_id' => $this->rentId,
                    'tipo' => $data['tipo'],
                    'mes_correspondiente' => $data['mes_correspondiente'],
                    'fecha_pago' => $data['fecha_pago'],
                    'monto' => $data['monto'],
                    'forma_pago' => $data['forma_pago'],
                    'observaciones' => $data['observaciones'] ?? null,
                    'estatus' => $estatus, // Guardamos el estatus calculado
                    'evidencia' => $data['evidencia'] ?? null,
                ]);

                Notification::make()->title('Pago registrado')->success()->send();
            });
    }

    public function verPagoAction(): Action
    {
        return Action::make('verPago') 
            ->label('Detalles')
            ->modalHeading('Detalles del pago')
            ->modalWidth('md')
            ->model(Service::class)
            ->record(fn (array $arguments) => Service::find($arguments['record'] ?? null))
            ->fillForm(fn (Service $record) => [
                'tipo' => strtoupper($record->tipo),
                'estatus_visual' => $record->estatus, 
                'mes_correspondiente' => $record->mes_correspondiente,
                'fecha_pago' => $record->fecha_pago,
                'forma_pago' => $record->forma_pago,
                'monto' => $record->monto,
                'observaciones' => $record->observaciones,
                'evidencia' => $record->evidencia,
            ])
            ->form([
                // Alerta visual de estatus
                Forms\Components\Placeholder::make('alerta_estatus')
                    ->hiddenLabel()
                    ->content(fn ($record) => match($record->estatus) {
                        'vencido' => new \Illuminate\Support\HtmlString('<div class="bg-red-100 text-red-700 p-2 rounded text-center font-bold">¡PAGO VENCIDO!</div>'),
                        'atrasado' => new \Illuminate\Support\HtmlString('<div class="bg-yellow-100 text-yellow-700 p-2 rounded text-center font-bold">PAGO ATRASADO</div>'),
                        default => new \Illuminate\Support\HtmlString('<div class="bg-green-100 text-green-700 p-2 rounded text-center font-bold">PAGADO A TIEMPO</div>'),
                    }),

                Forms\Components\TextInput::make('tipo')->label('Servicio')->disabled(),
                Forms\Components\TextInput::make('mes_correspondiente')->label('Mes')->disabled(),
                Forms\Components\DatePicker::make('fecha_pago')->label('Fecha')->disabled(),
                Forms\Components\TextInput::make('monto')->prefix('$')->disabled(),
                Forms\Components\FileUpload::make('evidencia')->image()->openable()->downloadable()->disabled(),
                Forms\Components\Textarea::make('observaciones')->disabled(),
            ])
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar');
    }
}