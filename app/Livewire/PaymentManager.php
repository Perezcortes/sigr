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

    public string $viewMode = 'calendario';

    public function mount($rentId)
    {
        $this->rentId = $rentId;
        $this->selectedMonth = (int) now()->month;
        $this->selectedYear = (int) now()->year;
    }

    public function render()
    {
        $obligations = $this->buildMonthlyObligations();
        $calendar = $this->buildMonthlyCalendar($obligations);

        return view('livewire.payment-manager', [
            'calendar' => $calendar,
            'monthlySummary' => $obligations,
            'monthLabel' => Carbon::create($this->selectedYear, $this->selectedMonth, 1)->translatedFormat('F Y'),
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $obligations
     * @return array<int, array<string, mixed>>
     */
    private function buildMonthlyCalendar(array $obligations): array
    {
        $start = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();

        $reportedServices = Service::where('rent_id', $this->rentId)
            ->whereDate('fecha_pago', '>=', $start)
            ->whereDate('fecha_pago', '<=', $end)
            ->when($this->filterType !== 'todos', fn ($query) => $query->where('tipo', $this->filterType))
            ->get();

        $days = [];
        for ($day = 1; $day <= $start->daysInMonth; $day++) {
            $date = Carbon::create($this->selectedYear, $this->selectedMonth, $day)->startOfDay();
            $events = [];

            foreach ($obligations as $obligation) {
                /** @var Carbon $dueDate */
                $dueDate = $obligation['due_date'];
                if (! $dueDate->isSameDay($date)) {
                    continue;
                }

                $events[] = [
                    'id' => 'scheduled-'.$obligation['key'],
                    'tipo' => $obligation['tipo'],
                    'source' => 'programado',
                    'monto' => $obligation['monto_esperado'],
                    'status' => $obligation['status'],
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

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildMonthlyObligations(): array
    {
        $monthStart = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->startOfDay();
        $periodKey = $monthStart->format('Y-m');
        $today = now()->startOfDay();

        $settings = PaymentSetting::where('rent_id', $this->rentId)
            ->where('activo', true)
            ->when($this->filterType !== 'todos', fn ($query) => $query->where('tipo', $this->filterType))
            ->orderByDesc('es_base_renta')
            ->orderBy('tipo')
            ->get();

        $serviceMatches = Service::where('rent_id', $this->rentId)
            ->whereNotNull('payment_setting_id')
            ->where('periodo_referencia', $periodKey)
            ->whereIn('payment_setting_id', $settings->pluck('id'))
            ->orderByDesc('fecha_pago')
            ->get()
            ->keyBy('payment_setting_id');

        $obligations = [];

        foreach ($settings as $setting) {
            $dueDate = $this->resolveDueDateForMonth($setting, $monthStart);
            if (! $dueDate) {
                continue;
            }

            $matchedService = $serviceMatches->get($setting->id);
            $paid = (bool) $matchedService;

            $obligations[] = [
                'key' => $setting->id.'|'.$periodKey,
                'payment_setting_id' => $setting->id,
                'tipo' => $setting->tipo,
                'frecuencia' => $setting->frecuencia ?? 'Mensual',
                'period_key' => $periodKey,
                'due_date' => $dueDate,
                'monto_esperado' => (float) ($setting->monto ?? 0),
                'monto_pagado' => $matchedService ? (float) $matchedService->monto : null,
                'status' => $this->resolveStatus($dueDate, $paid, $today),
                'paid_service_id' => $matchedService?->id,
            ];
        }

        return $obligations;
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
            ->mountUsing(function (Forms\Form $form, array $arguments): void {
                if (! empty($arguments['obligation'])) {
                    $form->fill([
                        'obligation_key' => (string) $arguments['obligation'],
                    ]);
                }
            })
            ->form([
                Forms\Components\Select::make('obligation_key')
                    ->label('Obligación a pagar')
                    ->helperText('Selecciona el servicio y periodo para vincular correctamente el pago.')
                    ->options(fn (): array => $this->getUnpaidObligationSelectOptions())
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (?string $state, Forms\Set $set): void {
                        $obligation = $this->findObligationByKey($state);
                        if (! $obligation) {
                            return;
                        }

                        $periodDate = Carbon::createFromFormat('Y-m', $obligation['period_key']);

                        $set('tipo', $obligation['tipo']);
                        $set('payment_setting_id', $obligation['payment_setting_id']);
                        $set('periodo_referencia', $obligation['period_key']);
                        $set('mes_correspondiente', ucfirst($periodDate->translatedFormat('F')));
                        $set('fecha_vencimiento', $obligation['due_date']->format('Y-m-d'));
                        $set('monto', $obligation['monto_esperado']);
                    })
                    ->columnSpanFull(),

                Forms\Components\Group::make()->schema([
                    Forms\Components\TextInput::make('tipo')
                        ->label('Servicio')
                        ->disabled()
                        ->dehydrated(),

                    Forms\Components\TextInput::make('mes_correspondiente')
                        ->label('Mes correspondiente')
                        ->disabled()
                        ->dehydrated(),

                    Forms\Components\DatePicker::make('fecha_pago')
                        ->default(now())->required(),

                    Forms\Components\DatePicker::make('fecha_vencimiento')
                        ->label('Fecha de vencimiento')
                        ->disabled()
                        ->dehydrated(),

                    Forms\Components\Select::make('forma_pago')
                        ->options(['efectivo' => 'Efectivo', 'tarjeta' => 'Tarjeta', 'transferencia' => 'Transferencia'])
                        ->default('efectivo')->required(),

                    Forms\Components\TextInput::make('monto')->numeric()->prefix('$')->required(),
                    Forms\Components\Hidden::make('payment_setting_id'),
                    Forms\Components\Hidden::make('periodo_referencia'),
                ])->columns(1),

                Forms\Components\FileUpload::make('evidencia')->image()->directory('comprobantes')->columnSpanFull(),
                Forms\Components\Textarea::make('observaciones')->rows(2)->columnSpanFull(),
            ])
            ->action(function (array $data) {
                $obligation = $this->findObligationByKey((string) ($data['obligation_key'] ?? ''));
                if (! $obligation) {
                    Notification::make()->title('La obligación seleccionada ya no está disponible')->danger()->send();

                    return;
                }

                $alreadyPaid = Service::where('rent_id', $this->rentId)
                    ->where('payment_setting_id', $obligation['payment_setting_id'])
                    ->where('periodo_referencia', $obligation['period_key'])
                    ->exists();

                if ($alreadyPaid) {
                    Notification::make()->title('Ese periodo ya fue reportado como pagado')->warning()->send();

                    return;
                }

                $dueDate = $obligation['due_date'] instanceof Carbon
                    ? $obligation['due_date']->copy()
                    : Carbon::parse((string) $data['fecha_vencimiento']);
                $paidDate = Carbon::parse($data['fecha_pago']);
                $estatus = $paidDate->gt($dueDate) ? 'atrasado' : 'pagado';

                Service::create([
                    'rent_id' => $this->rentId,
                    'payment_setting_id' => $obligation['payment_setting_id'],
                    'nombre' => trim(($obligation['tipo'] ?? 'Pago').' - '.($obligation['period_key'] ?? '')),
                    'tipo' => $obligation['tipo'],
                    'frecuencia' => $obligation['frecuencia'] ?? 'Mensual',
                    'mes_correspondiente' => ucfirst(Carbon::createFromFormat('Y-m', $obligation['period_key'])->translatedFormat('F')),
                    'fecha_pago' => $data['fecha_pago'],
                    'fecha_vencimiento' => $dueDate->toDateString(),
                    'monto' => $data['monto'],
                    'forma_pago' => $data['forma_pago'],
                    'observaciones' => $data['observaciones'] ?? null,
                    'estatus' => $estatus,
                    'evidencia' => $data['evidencia'] ?? null,
                    'periodo_referencia' => $obligation['period_key'],
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

    /**
     * @return array<string, string>
     */
    private function getUnpaidObligationSelectOptions(): array
    {
        return collect($this->buildMonthlyObligations())
            ->filter(fn (array $obligation): bool => empty($obligation['paid_service_id']))
            ->mapWithKeys(function (array $obligation): array {
                /** @var Carbon $dueDate */
                $dueDate = $obligation['due_date'];
                $label = ucfirst($obligation['tipo']).' · '.$obligation['period_key'].' · vence '.$dueDate->format('d/m');

                return [$obligation['key'] => $label];
            })
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findObligationByKey(?string $key): ?array
    {
        if (! $key) {
            return null;
        }

        foreach ($this->buildMonthlyObligations() as $obligation) {
            if ($obligation['key'] === $key) {
                return $obligation;
            }
        }

        return null;
    }
}
