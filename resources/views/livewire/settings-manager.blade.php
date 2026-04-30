<div class="space-y-6">
    
    {{ $this->form }}

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-100 border-t-4 border-t-[#26cad3] dark:border-gray-700 p-6">
        
        <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-6">Configuración de pagos</h3>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left border-b border-gray-100 dark:border-gray-700">
                        <th class="py-2 pr-3 font-semibold text-gray-600 dark:text-gray-300">Tipo</th>
                        <th class="py-2 px-3 font-semibold text-gray-600 dark:text-gray-300">Activo</th>
                        <th class="py-2 px-3 font-semibold text-gray-600 dark:text-gray-300">Frecuencia</th>
                        <th class="py-2 px-3 font-semibold text-gray-600 dark:text-gray-300">Programación de pago</th>
                        <th class="py-2 px-3 font-semibold text-gray-600 dark:text-gray-300">Variable</th>
                        <th class="py-2 px-3 font-semibold text-gray-600 dark:text-gray-300">Monto</th>
                        <th class="py-2 pl-3 font-semibold text-gray-600 dark:text-gray-300">Recordatorios</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($paymentSettings as $payment)
                        <tr class="border-b border-gray-50 dark:border-gray-700/60" wire:key="payment-row-{{ $payment->id }}">
                            <td class="py-3 pr-3">
                                <span class="font-semibold text-gray-900 dark:text-white capitalize">{{ $payment->tipo }}</span>
                                @if($payment->es_base_renta)
                                    <span class="ml-2 text-xs px-2 py-0.5 rounded bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">Base</span>
                                @endif
                            </td>
                            <td class="py-3 px-3">
                                <input
                                    type="checkbox"
                                    class="rounded border-gray-300"
                                    @checked($payment->activo)
                                    wire:click="togglePayment({{ $payment->id }})"
                                />
                            </td>
                            <td class="py-3 px-3">
                                <select
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900"
                                    wire:change="updatePaymentField({{ $payment->id }}, 'frecuencia', $event.target.value)"
                                >
                                    <option value="Mensual" @selected($payment->frecuencia === 'Mensual')>Mensual</option>
                                    <option value="Bimestral" @selected($payment->frecuencia === 'Bimestral')>Bimestral</option>
                                    <option value="Trimestral" @selected($payment->frecuencia === 'Trimestral')>Trimestral</option>
                                    <option value="Semestral" @selected($payment->frecuencia === 'Semestral')>Semestral</option>
                                    <option value="Anual" @selected($payment->frecuencia === 'Anual')>Anual</option>
                                </select>
                            </td>
                            <td class="py-3 px-3">
                                @if($payment->frecuencia === 'Mensual')
                                    <input
                                        type="number"
                                        min="1"
                                        max="31"
                                        value="{{ $payment->dia_pago }}"
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900"
                                        wire:change="updatePaymentField({{ $payment->id }}, 'dia_pago', $event.target.value)"
                                    />
                                    <p class="mt-1 text-[11px] text-gray-500">Captura el día del mes para cobrar.</p>
                                @else
                                    <input
                                        type="date"
                                        value="{{ optional($payment->fecha_limite_pago)->format('Y-m-d') }}"
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900"
                                        wire:change="updatePaymentField({{ $payment->id }}, 'fecha_limite_pago', $event.target.value)"
                                    />
                                    <p class="mt-1 text-[11px] text-gray-500">Fecha límite del último pago para calcular el siguiente.</p>
                                @endif
                            </td>
                            <td class="py-3 px-3">
                                <input
                                    type="checkbox"
                                    class="rounded border-gray-300"
                                    @checked($payment->es_variable)
                                    wire:change="updatePaymentField({{ $payment->id }}, 'es_variable', $event.target.checked)"
                                />
                            </td>
                            <td class="py-3 px-3">
                                <input
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    value="{{ $payment->monto }}"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900"
                                    wire:change="updatePaymentField({{ $payment->id }}, 'monto', $event.target.value)"
                                    @disabled($payment->es_variable)
                                />
                            </td>
                            <td class="py-3 pl-3">
                                <div class="space-y-2">
                                    @foreach($payment->reminders as $reminder)
                                        <div class="flex items-center gap-2" wire:key="reminder-row-{{ $reminder->id }}">
                                            <select
                                                class="w-24 rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900"
                                                wire:change="updateReminderDirection({{ $reminder->id }}, $event.target.value)"
                                            >
                                                <option value="antes" @selected($reminder->direccion === 'antes')>Antes</option>
                                                <option value="despues" @selected($reminder->direccion === 'despues')>Después</option>
                                            </select>
                                            <input
                                                type="number"
                                                min="0"
                                                class="w-20 rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900"
                                                value="{{ $reminder->dias_antes }}"
                                                wire:change="updateReminderDays({{ $reminder->id }}, $event.target.value)"
                                            />
                                            <span class="text-xs text-gray-500">días</span>
                                            <button
                                                type="button"
                                                class="text-xs text-red-600 hover:underline"
                                                wire:click="removeReminder({{ $reminder->id }})"
                                            >
                                                Quitar
                                            </button>
                                        </div>
                                    @endforeach
                                    <button
                                        type="button"
                                        class="text-xs text-[#FF5A1F] hover:underline font-semibold"
                                        wire:click="addReminder({{ $payment->id }})"
                                    >
                                        + Agregar recordatorio
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            <button
                type="button"
                class="px-4 py-2 rounded-lg bg-[#FF5A1F] text-white text-sm font-semibold hover:opacity-90"
                wire:click="addPaymentSetting"
            >
                + Agregar tipo de pago
            </button>
        </div>
        
    </div>

    <x-filament-actions::modals />
</div>