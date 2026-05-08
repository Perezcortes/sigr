<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <button type="button" class="px-3 py-1.5 rounded border border-gray-300 text-sm" wire:click="prevMonth">Anterior</button>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white capitalize">{{ $monthLabel }}</h3>
            <button type="button" class="px-3 py-1.5 rounded border border-gray-300 text-sm" wire:click="nextMonth">Siguiente</button>
        </div>

        <div class="flex items-center gap-2">
            <select
                class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm"
                wire:model.live="filterType"
            >
                <option value="todos">Todos los tipos</option>
                <option value="renta">Renta</option>
                <option value="luz">Luz</option>
                <option value="agua">Agua</option>
                <option value="gas">Gas</option>
                <option value="mantenimiento">Mantenimiento</option>
            </select>

            {{ $this->reportarPagoAction }}
        </div>
    </div>

    <div class="flex items-center gap-2">
        <button
            type="button"
            wire:click="$set('viewMode', 'calendario')"
            class="px-3 py-1.5 rounded-lg text-sm font-semibold border {{ $viewMode === 'calendario' ? 'bg-[#161848] text-white border-[#161848]' : 'bg-white text-gray-700 border-gray-300 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600' }}"
        >
            Calendario
        </button>
        <button
            type="button"
            wire:click="$set('viewMode', 'resumen')"
            class="px-3 py-1.5 rounded-lg text-sm font-semibold border {{ $viewMode === 'resumen' ? 'bg-[#161848] text-white border-[#161848]' : 'bg-white text-gray-700 border-gray-300 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600' }}"
        >
            Resumen mensual
        </button>
    </div>

    @if($viewMode === 'calendario')
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">
            @foreach($calendar as $day)
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-3 bg-white dark:bg-gray-800">
                    <div class="text-xs font-semibold text-gray-500 mb-2">
                        {{ $day['date']->format('d M') }}
                    </div>

                    <div class="space-y-2">
                        @forelse($day['events'] as $event)
                            @php
                                $statusClass = match($event['status']) {
                                    'pagado' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                                    'vencido' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                                    default => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
                                };
                            @endphp
                            <div class="rounded-lg px-2 py-1 {{ $statusClass }}">
                                <div class="text-xs font-semibold capitalize">{{ $event['tipo'] }}</div>
                                <div class="text-[11px]">${{ number_format((float) $event['monto'], 2) }} · {{ $event['source'] }}</div>
                            </div>
                        @empty
                            <div class="text-xs text-gray-400">Sin pagos</div>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700 text-left">
                        <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-300">Servicio</th>
                        <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-300">Vencimiento</th>
                        <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-300">Monto pagado</th>
                        <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-300">Estatus</th>
                        <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-300">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($monthlySummary as $obligation)
                        @php
                            $statusClass = match($obligation['status']) {
                                'pagado' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                                'vencido' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                                default => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
                            };
                        @endphp
                        <tr class="border-b border-gray-100 dark:border-gray-700/60">
                            <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white capitalize">{{ $obligation['tipo'] }}</td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-200">{{ $obligation['due_date']->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-200">
                                @if(!is_null($obligation['monto_pagado']))
                                    ${{ number_format((float) $obligation['monto_pagado'], 2) }}
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-md px-2 py-1 text-xs font-semibold {{ $statusClass }}">
                                    {{ str_replace('_', ' ', $obligation['status']) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if($obligation['paid_service_id'])
                                    <button
                                        type="button"
                                        class="text-xs font-semibold text-[#161848] hover:underline dark:text-[#26cad3]"
                                        wire:click="mountAction('verPago', { record: {{ $obligation['paid_service_id'] }} })"
                                    >
                                        Ver pago
                                    </button>
                                @else
                                    <button
                                        type="button"
                                        class="text-xs font-semibold text-[#FF5A1F] hover:underline"
                                        wire:click="mountAction('reportarPago', { obligation: '{{ $obligation['key'] }}' })"
                                    >
                                        Reportar pago
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-gray-400">Sin obligaciones para este mes.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
    
    <x-filament-actions::modals />
</div>