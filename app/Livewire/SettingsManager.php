<?php

namespace App\Livewire;

use App\Models\PaymentReminder;
use App\Models\PaymentSetting;
use App\Models\Rent;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;
use Livewire\Component;

class SettingsManager extends Component implements HasForms
{
    use InteractsWithForms;

    private const NOTIFICATION_FIELDS = [
        'notif_recordatorios_email',
        'notif_recordatorios_push',
        'notif_recordatorios_whatsapp',
        'notif_reporte_pago_email',
        'notif_reporte_pago_push',
        'notif_reporte_pago_whatsapp',
        'notif_mensajes_email',
        'notif_mensajes_push',
        'notif_mensajes_whatsapp',
        'notif_mantenimiento_email',
        'notif_mantenimiento_push',
        'notif_mantenimiento_whatsapp',
    ];

    public $rentId;

    public ?array $data = []; // Datos del formulario de notificaciones

    public bool $isInitializingForm = true;

    public function mount(int $rentId): void
    {
        $this->rentId = $rentId;
        $record = Rent::findOrFail($rentId);
        $this->form->fill($record->attributesToArray());
        $this->ensureBaseRentPayment($record);
        $this->ensureDefaultUtilityPayments();
        $this->isInitializingForm = false;
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
                ])->extraAttributes(['class' => 'bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-100 border-t-4 border-t-[#26cad3] shadow-sm mb-6']),

                // 2. SECCIÓN NOTIFICACIONES
                Forms\Components\Section::make('Notificaciones')
                    ->extraAttributes(['class' => 'shadow-sm border-gray-100'])
                    ->schema([
                        Forms\Components\Grid::make(4)
                            ->extraAttributes([
                                // Filament fuerza `inline-flex` en el switch; con eso `mx-auto` no centra. Forzamos flex en bloque + márgenes.
                                'class' => '[&_button.fi-fo-toggle]:!flex [&_button.fi-fo-toggle]:!mx-auto',
                            ])
                            ->schema([
                                Forms\Components\Placeholder::make('notif_header_tipo')
                                    ->hiddenLabel()
                                    ->extraAttributes(['class' => 'flex min-h-6 items-center'])
                                    ->content(new HtmlString('<span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Tipo de notificación</span>')),
                                Forms\Components\Placeholder::make('notif_header_email')
                                    ->hiddenLabel()
                                    ->extraAttributes(['class' => 'flex min-h-6 items-left justify-left text-left'])
                                    ->content(new HtmlString('<span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Email</span>')),
                                Forms\Components\Placeholder::make('notif_header_push')
                                    ->hiddenLabel()
                                    ->extraAttributes(['class' => 'flex min-h-6 items-left justify-left text-left'])
                                    ->content(new HtmlString('<span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Push</span>')),
                                Forms\Components\Placeholder::make('notif_header_whatsapp')
                                    ->hiddenLabel()
                                    ->extraAttributes(['class' => 'flex min-h-6 items-left justify-left text-left'])
                                    ->content(new HtmlString('<span class="text-xs font-semibold uppercase tracking-wide text-gray-500">WhatsApp</span>')),

                                Forms\Components\Placeholder::make('notif_row_recordatorios')
                                    ->hiddenLabel()
                                    ->extraAttributes(['class' => 'flex min-h-11 items-center'])
                                    ->content(new HtmlString('<span class="text-sm font-medium text-gray-800 dark:text-gray-100">Recordatorios de pago</span>')),
                                Forms\Components\Toggle::make('notif_recordatorios_email')->hiddenLabel()->default(true)->onColor('warning')->live(),
                                Forms\Components\Toggle::make('notif_recordatorios_push')->hiddenLabel()->default(true)->onColor('warning')->live(),
                                Forms\Components\Toggle::make('notif_recordatorios_whatsapp')->hiddenLabel()->default(true)->onColor('warning')->live(),

                                Forms\Components\Placeholder::make('notif_row_reporte_pago')
                                    ->hiddenLabel()
                                    ->extraAttributes(['class' => 'flex min-h-11 items-center'])
                                    ->content(new HtmlString('<span class="text-sm font-medium text-gray-800 dark:text-gray-100">Reporte de pago</span>')),
                                Forms\Components\Toggle::make('notif_reporte_pago_email')->hiddenLabel()->default(true)->onColor('warning')->live(),
                                Forms\Components\Toggle::make('notif_reporte_pago_push')->hiddenLabel()->default(true)->onColor('warning')->live(),
                                Forms\Components\Toggle::make('notif_reporte_pago_whatsapp')->hiddenLabel()->default(true)->onColor('warning')->live(),

                                Forms\Components\Placeholder::make('notif_row_mensajes')
                                    ->hiddenLabel()
                                    ->extraAttributes(['class' => 'flex min-h-11 items-center'])
                                    ->content(new HtmlString('<span class="text-sm font-medium text-gray-800 dark:text-gray-100">Mensajes</span>')),
                                Forms\Components\Toggle::make('notif_mensajes_email')->hiddenLabel()->default(true)->onColor('warning')->live(),
                                Forms\Components\Toggle::make('notif_mensajes_push')->hiddenLabel()->default(true)->onColor('warning')->live(),
                                Forms\Components\Toggle::make('notif_mensajes_whatsapp')->hiddenLabel()->default(true)->onColor('warning')->live(),

                                Forms\Components\Placeholder::make('notif_row_mantenimiento')
                                    ->hiddenLabel()
                                    ->extraAttributes(['class' => 'flex min-h-11 items-center'])
                                    ->content(new HtmlString('<span class="text-sm font-medium text-gray-800 dark:text-gray-100">Reporte de mantenimiento</span>')),
                                Forms\Components\Toggle::make('notif_mantenimiento_email')->hiddenLabel()->default(true)->onColor('warning')->live(),
                                Forms\Components\Toggle::make('notif_mantenimiento_push')->hiddenLabel()->default(true)->onColor('warning')->live(),
                                Forms\Components\Toggle::make('notif_mantenimiento_whatsapp')->hiddenLabel()->default(true)->onColor('warning')->live(),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    // Guardar Notificaciones (se llama automáticamente o con wire:change)
    public function updatedData()
    {
        if ($this->isInitializingForm) {
            return;
        }

        $notificationData = array_intersect_key($this->data, array_flip(self::NOTIFICATION_FIELDS));
        Rent::find($this->rentId)?->update($notificationData);

        Notification::make()
            ->title('Preferencia actualizada')
            ->success()
            ->send();
    }

    public function addPaymentSetting(): void
    {
        $nextType = $this->nextSuggestedPaymentType();

        $setting = PaymentSetting::create([
            'rent_id' => $this->rentId,
            'tipo' => $nextType,
            'frecuencia' => 'Mensual',
            'dia_pago' => 5,
            'meses_intervalo' => PaymentSetting::intervalForFrequency('Mensual'),
            'monto' => 0,
            'moneda' => 'MXN',
            'es_variable' => true,
            // Solo la renta base inicia activa por defecto.
            'activo' => false,
            'es_base_renta' => false,
        ]);

        PaymentReminder::create([
            'payment_setting_id' => $setting->id,
            'dias_antes' => 3,
            'direccion' => 'antes',
            'activo' => true,
        ]);

        Notification::make()->title('Pago agregado')->success()->send();
    }

    public function updatePaymentField(int $paymentId, string $field, mixed $value): void
    {
        $payment = PaymentSetting::where('rent_id', $this->rentId)->find($paymentId);

        if (! $payment) {
            return;
        }

        if ($field === 'frecuencia') {
            $allowed = array_keys(PaymentSetting::FREQUENCY_INTERVALS);
            $value = in_array($value, $allowed, true) ? $value : 'Mensual';
            $payment->meses_intervalo = PaymentSetting::intervalForFrequency($value);

            if ($value === 'Mensual') {
                $payment->fecha_limite_pago = null;
                $payment->dia_pago = $payment->dia_pago ?: 5;
            } else {
                $payment->dia_pago = null;
                $payment->fecha_limite_pago = $payment->fecha_limite_pago ?? now()->toDateString();
            }
        }

        if (in_array($field, ['dia_pago', 'monto', 'fecha_limite_pago'], true)) {
            $value = $value === '' ? null : $value;
        }

        if ($field === 'es_variable') {
            $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            if ($value) {
                $payment->monto = null;
            }
        }

        $payment->{$field} = $value;
        $payment->save();

        Notification::make()->title('Pago actualizado')->success()->send();
    }

    public function togglePayment(int $paymentId): void
    {
        $payment = PaymentSetting::where('rent_id', $this->rentId)->find($paymentId);

        if (! $payment) {
            return;
        }

        if ($payment->es_base_renta) {
            Notification::make()->title('La renta base no se puede desactivar')->warning()->send();

            return;
        }

        $payment->activo = ! $payment->activo;
        $payment->save();

        Notification::make()->title('Estado de pago actualizado')->success()->send();
    }

    public function addReminder(int $paymentId): void
    {
        $payment = PaymentSetting::where('rent_id', $this->rentId)->find($paymentId);

        if (! $payment) {
            return;
        }

        $nextDays = (int) ($payment->reminders()->max('dias_antes') ?? 0) + 1;

        $payment->reminders()->create([
            'dias_antes' => $nextDays,
            'direccion' => 'antes',
            'activo' => true,
        ]);

        Notification::make()->title('Recordatorio agregado')->success()->send();
    }

    public function removeReminder(int $reminderId): void
    {
        $reminder = PaymentReminder::whereHas('paymentSetting', fn ($query) => $query->where('rent_id', $this->rentId))
            ->find($reminderId);

        if (! $reminder) {
            return;
        }

        $reminder->delete();
        Notification::make()->title('Recordatorio eliminado')->success()->send();
    }

    public function updateReminderDays(int $reminderId, mixed $days): void
    {
        $reminder = PaymentReminder::whereHas('paymentSetting', fn ($query) => $query->where('rent_id', $this->rentId))
            ->find($reminderId);

        if (! $reminder) {
            return;
        }

        $reminder->dias_antes = max(0, (int) $days);
        $reminder->save();

        Notification::make()->title('Recordatorio actualizado')->success()->send();
    }

    public function updateReminderDirection(int $reminderId, string $direction): void
    {
        $reminder = PaymentReminder::whereHas('paymentSetting', fn ($query) => $query->where('rent_id', $this->rentId))
            ->find($reminderId);

        if (! $reminder) {
            return;
        }

        $reminder->direccion = in_array($direction, ['antes', 'despues'], true) ? $direction : 'antes';
        $reminder->save();

        Notification::make()->title('Recordatorio actualizado')->success()->send();
    }

    private function ensureBaseRentPayment(Rent $rent): void
    {
        $exists = PaymentSetting::where('rent_id', $this->rentId)
            ->where('es_base_renta', true)
            ->exists();

        if ($exists) {
            return;
        }

        $base = PaymentSetting::create([
            'rent_id' => $this->rentId,
            'tipo' => 'renta',
            'frecuencia' => 'Mensual',
            'dia_pago' => 5,
            'meses_intervalo' => PaymentSetting::intervalForFrequency('Mensual'),
            'monto' => $rent->monto ?? $rent->renta ?? 0,
            'moneda' => 'MXN',
            'es_variable' => false,
            'activo' => true,
            'es_base_renta' => true,
        ]);

        $base->reminders()->create([
            'dias_antes' => 3,
            'direccion' => 'antes',
            'activo' => true,
        ]);
    }

    private function ensureDefaultUtilityPayments(): void
    {
        $types = ['mantenimiento', 'agua', 'luz'];

        foreach ($types as $type) {
            $exists = PaymentSetting::where('rent_id', $this->rentId)
                ->where('tipo', $type)
                ->exists();

            if ($exists) {
                continue;
            }

            PaymentSetting::create([
                'rent_id' => $this->rentId,
                'tipo' => $type,
                'frecuencia' => 'Mensual',
                'dia_pago' => 5,
                'meses_intervalo' => PaymentSetting::intervalForFrequency('Mensual'),
                'monto' => 0,
                'moneda' => 'MXN',
                'es_variable' => true,
                'activo' => false,
                'es_base_renta' => false,
            ]);
        }
    }

    private function nextSuggestedPaymentType(): string
    {
        $existingTypes = PaymentSetting::where('rent_id', $this->rentId)
            ->pluck('tipo')
            ->map(fn (mixed $type): string => mb_strtolower((string) $type))
            ->all();

        foreach (['mantenimiento', 'agua', 'luz'] as $candidate) {
            if (! in_array($candidate, $existingTypes, true)) {
                return $candidate;
            }
        }

        return 'agua';
    }

    public function render()
    {
        $paymentSettings = PaymentSetting::where('rent_id', $this->rentId)
            ->with('reminders')
            ->orderByRaw("
                CASE
                    WHEN es_base_renta = 1 THEN 0
                    WHEN tipo = 'mantenimiento' THEN 1
                    WHEN tipo = 'agua' THEN 2
                    WHEN tipo = 'luz' THEN 3
                    ELSE 4
                END
            ")
            ->orderBy('id')
            ->get();

        return view('livewire.settings-manager', [
            'paymentSettings' => $paymentSettings,
        ]);
    }
}
