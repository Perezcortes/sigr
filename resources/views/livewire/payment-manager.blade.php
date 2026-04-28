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
    
    <x-filament-actions::modals />
</div>