<div class="space-y-4">
    <div class="flex justify-end">
        {{ $this->reportarPagoAction }}
    </div>

    @forelse($serviciosPorMes as $mes => $items)
        <div x-data="{ open: false }" class="bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700 overflow-hidden mb-3">
            
            <button @click="open = !open" 
                    class="report-row flex items-center justify-between w-full p-4 transition rounded-xl">
                
                <div class="flex items-center gap-3">
                    <span class="font-bold text-gray-800 dark:text-white capitalize text-lg">{{ $mes }}</span>
                </div>
                
                <svg class="w-5 h-5 text-gray-400 transform transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </button>

            <div x-show="open" x-collapse class="border-t border-gray-100 dark:border-gray-700">
                @foreach($items as $item)
                    <div wire:click="mountAction('verPago', { record: {{ $item->id }} })" 
                         class="report-row grid grid-cols-12 items-center p-4 border-b border-gray-50 dark:border-gray-700 last:border-0 gap-4">
                        
                        <div class="col-span-4 flex items-center">
                            <span class="font-medium text-gray-700 dark:text-gray-200 capitalize text-base">
                                {{ $item->tipo }}
                            </span>
                        </div>

                        <div class="col-span-6 flex justify-center">
                            @if($item->estatus === 'pagado')
                                <span class="text-sm text-gray-500 font-medium dark:text-gray-400">
                                    {{ \Carbon\Carbon::parse($item->fecha_pago)->format('d/m/y') }}
                                </span>
                            @elseif($item->estatus === 'atrasado')
                                <span class="text-xs font-bold text-yellow-700 bg-yellow-100 border border-yellow-200 px-2 py-1 rounded-md dark:bg-yellow-900/30 dark:text-yellow-400 dark:border-yellow-700">
                                    Atrasado
                                </span>
                            @elseif($item->estatus === 'vencido')
                                <span class="text-xs font-bold text-red-700 bg-red-100 border border-red-200 px-2 py-1 rounded-md dark:bg-red-900/30 dark:text-red-400 dark:border-red-700">
                                    Vencido
                                </span>
                            @else
                                <span class="text-sm text-gray-400 capitalize">{{ $item->estatus }}</span>
                            @endif
                        </div>

                        <div class="col-span-2 flex justify-end">
                            <div class="border p-2 rounded-lg shadow-sm transition-colors
                                {{ match($item->estatus) { 
                                    'pagado' => 'border-green-200 bg-green-50 text-green-600 dark:bg-green-900/20 dark:border-green-800 dark:text-green-400', 
                                    'vencido' => 'border-red-200 bg-red-50 text-red-600 dark:bg-red-900/20 dark:border-red-800 dark:text-red-400', 
                                    'atrasado' => 'border-yellow-200 bg-yellow-50 text-yellow-600 dark:bg-yellow-900/20 dark:border-yellow-800 dark:text-yellow-400', 
                                    default => 'border-gray-200 text-gray-500 dark:border-gray-700 dark:text-gray-400' 
                                } }}">
                                
                                @php
                                    // Selección del icono correcto (Heroicons v2)
                                    $iconStatus = match($item->tipo) {
                                        'renta' => 'heroicon-m-home',
                                        'luz' => 'heroicon-m-bolt',
                                        'agua' => 'heroicon-m-beaker', // Icono corregido
                                        'gas' => 'heroicon-m-fire',
                                        'mantenimiento' => 'heroicon-m-wrench',
                                        default => 'heroicon-m-banknotes'
                                    };
                                @endphp
                                <x-filament::icon :icon="$iconStatus" class="w-5 h-5" />
                            </div>
                        </div>

                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="text-center p-8 bg-gray-50 rounded-xl border border-dashed border-gray-300 dark:bg-gray-800/50 dark:border-gray-700">
            <x-filament::icon icon="heroicon-o-currency-dollar" class="w-12 h-12 text-gray-300 mx-auto mb-3" />
            <p class="text-gray-500 dark:text-gray-400">No hay pagos registrados para esta administración.</p>
        </div>
    @endforelse
    
    <x-filament-actions::modals />
</div>