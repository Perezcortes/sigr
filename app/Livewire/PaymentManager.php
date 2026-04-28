<?php

namespace App\Livewire;

use App\Models\PaymentSetting;
use App\Models\Service;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;
use Livewire\Component;

class PaymentManager extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    public $rentId;

    public int $selectedMonth;

    public int $selectedYear;

    public string $filterType = 'todos';

    public function mount($rentId)
    {
        $this->rentId = $rentId;
        $this->selectedMonth = (int) now()->month;
        $this->selectedYear = (int) now()->year;
    }

    public function render()
    {
        $calendar = $this->buildMonthlyCalendar();

        return view('livewire.payment-manager', [
            'calendar' => $calendar,
            'monthLabel' => Carbon::create($this->selectedYear, $this->selectedMonth, 1)->translatedFormat('F Y'),
        ]);
    }

    private function buildMonthlyCalendar(): array
    {
        $start = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();
        $today = now()->startOfDay();

        $settings = PaymentSetting::where('rent_id', $this->rentId)
            ->where('activo', true)
            ->when($this->filterType !== 'todos', fn ($query) => $query->where('tipo', $this->filterType))
            ->with('services')
            ->get();

        $reportedServices = Service::where('rent_id', $this->rentId)
            ->whereDate('fecha_pago', '>=', $start)
            ->whereDate('fecha_pago', '<=', $end)
            ->when($this->filterType !== 'todos', fn ($query) => $query->where('tipo', $this->filterType))
            ->get();

        $days = [];
        for ($day = 1; $day <= $start->daysInMonth; $day++) {
            $date = Carbon::create($this->selectedYear, $this->selectedMonth, $day)->startOfDay();
            $events = [];

            foreach ($settings as $setting) {
                $dueDate = $this->resolveDueDateForMonth($setting, $date);
                if (! $dueDate || ! $dueDate->isSameDay($date)) {
                    continue;
                }

                $paid = $setting->services()
                    ->whereDate('fecha_pago', '>=', $dueDate->copy()->startOfMonth())
                    ->whereDate('fecha_pago', '<=', $dueDate->copy()->endOfMonth())
                    ->exists();

                $events[] = [
                    'id' => 'scheduled-'.$setting->id.'-'.$date->format('Ymd'),
                    'tipo' => $setting->tipo,
                    'source' => 'programado',
                    'monto' => $setting->monto,
                    'status' => $this->resolveStatus($dueDate, $paid, $today),
                ];
            }

            foreach ($reportedServices->where(fn ($service) => Carbon::parse($service->fecha_pago)->isSameDay($date)) as $service) {
                $events[] = [
                    'id' => 'reported-'.$service->id,
                    'tipo' => $service->tipo,
                    'source' => 'reportado',
                    'monto' => $service->monto,
                    'status' => 'pagado',
                ];
            }

            $days[] = [
                'date' => $date,
                'events' => $events,
            ];
        }

        return $days;
    }

    private function resolveDueDateForMonth(PaymentSetting $setting, Carbon $candidateDate): ?Carbon
    {
        $interval = max(1, (int) ($setting->meses_intervalo ?: PaymentSetting::intervalForFrequency($setting->frecuencia ?? 'Mensual')));

        if (($setting->frecuencia ?? 'Mensual') === 'Mensual') {
            $day = min(max((int) ($setting->dia_pago ?? 1), 1), $candidateDate->daysInMonth);

            return Carbon::create($candidateDate->year, $candidateDate->month, $day)->startOfDay();
        }

        if (! $setting->fecha_limite_pago) {
            return null;
        }

        $anchor = $setting->fecha_limite_pago->copy()->startOfDay();
        $diffMonths = (($candidateDate->year - $anchor->year) * 12) + ($candidateDate->month - $anchor->month);

        if ($diffMonths < 0 || $diffMonths % $interval !== 0) {
            return null;
        }

        $day = min($anchor->day, $candidateDate->daysInMonth);

        return Carbon::create($candidateDate->year, $candidateDate->month, $day)->startOfDay();
    }

    private function resolveStatus(Carbon $dueDate, bool $paid, Carbon $today): string
    {
        if ($paid) {
            return 'pagado';
        }

        if ($dueDate->lt($today)) {
            return 'vencido';
        }

        return 'por_vencer';
    }

    public function prevMonth(): void
    {
        $current = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->subMonth();
        $this->selectedMonth = (int) $current->month;
        $this->selectedYear = (int) $current->year;
    }

    public function nextMonth(): void
    {
        $current = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->addMonth();
        $this->selectedMonth = (int) $current->month;
        $this->selectedYear = (int) $current->year;
    }

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
                    'payment_setting_id' => null,
                    'nombre' => trim(($data['tipo'] ?? 'Pago').' - '.($data['mes_correspondiente'] ?? '')),
                    'tipo' => $data['tipo'],
                    'mes_correspondiente' => $data['mes_correspondiente'],
                    'fecha_pago' => $data['fecha_pago'],
                    'fecha_vencimiento' => $data['fecha_pago'],
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
                    ->content(fn ($record) => match ($record->estatus) {
                        'vencido' => new HtmlString('<div class="bg-red-100 text-red-700 p-2 rounded text-center font-bold">¡PAGO VENCIDO!</div>'),
                        'atrasado' => new HtmlString('<div class="bg-yellow-100 text-yellow-700 p-2 rounded text-center font-bold">PAGO ATRASADO</div>'),
                        default => new HtmlString('<div class="bg-green-100 text-green-700 p-2 rounded text-center font-bold">PAGADO A TIEMPO</div>'),
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
