<div class="space-y-6">
    <div class="flex justify-end">
        {{ $this->reportarPagoAction }}
    </div>

    <div class="space-y-4">
        @forelse($serviciosPorMes as $mes => $items)
            <div x-data="{ open: false }" class="bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700 overflow-hidden">
                
                <button @click="open = !open" 
                        class="flex items-center justify-between w-full p-4 transition-colors duration-200 
                               bg-gray-50 hover:bg-gray-100 
                               dark:bg-white/5 dark:hover:bg-white/10">
                    
                    <div class="flex items-center gap-3">
                        <x-filament::icon icon="heroicon-m-calendar-days" class="w-5 h-5 text-gray-400 dark:text-gray-500" />
                        <span class="font-bold text-gray-800 dark:text-white capitalize text-lg tracking-tight">{{ $mes }}</span>
                        <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                            {{ count($items) }}
                        </span>
                    </div>
                    
                    <svg class="w-5 h-5 text-gray-400 transform transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>

                <div x-show="open" x-collapse class="border-t border-gray-100 dark:border-gray-700 divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($items as $item)
                        <div wire:click="mountAction('verPago', { record: {{ $item->id }} })" 
                             class="report-row relative group p-4 flex items-center justify-between gap-4 transition-all hover:pl-5">
                            
                            <div class="flex items-center gap-4">
                                @php
                                    $colorClass = match($item->tipo) {
                                        'agua' => 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400',
                                        'gas' => 'bg-orange-100 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400',
                                        'luz' => 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900/30 dark:text-yellow-400',
                                        'renta' => 'bg-[#161848]/10 text-[#161848] dark:bg-[#26cad3]/20 dark:text-[#26cad3]',
                                        'mantenimiento' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                                        default => 'bg-gray-100 text-gray-500'
                                    };
                                    
                                    $iconName = match($item->tipo) {
                                        'renta' => 'heroicon-m-home',
                                        'luz' => 'heroicon-m-bolt',
                                        'agua' => 'heroicon-m-beaker',
                                        'gas' => 'heroicon-m-fire',
                                        'mantenimiento' => 'heroicon-m-wrench',
                                        default => 'heroicon-m-banknotes'
                                    };
                                @endphp

                                <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center {{ $colorClass }}">
                                    <x-filament::icon :icon="$iconName" class="w-5 h-5" />
                                </div>

                                <div class="flex flex-col">
                                    <span class="font-bold text-gray-900 dark:text-white capitalize text-sm md:text-base">
                                        {{ $item->tipo }}
                                    </span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{-- Si tienes el monto disponible en $item, ponlo aquí, si no, deja el texto genérico --}}
                                        Recibo de servicio
                                    </span>
                                </div>
                            </div>

                            <div class="flex flex-col items-end gap-1">
                                @if($item->estatus === 'pagado')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 border border-green-200 dark:border-green-800">
                                        Pagado
                                    </span>
                                @elseif($item->estatus === 'atrasado')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400 border border-yellow-200 dark:border-yellow-800">
                                        Atrasado
                                    </span>
                                @elseif($item->estatus === 'vencido')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 border border-red-200 dark:border-red-800">
                                        Vencido
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                        {{ ucfirst($item->estatus) }}
                                    </span>
                                @endif

                                <span class="text-xs text-gray-400 dark:text-gray-500 font-medium flex items-center gap-1">
                                    <x-filament::icon icon="heroicon-m-clock" class="w-3 h-3" />
                                    {{ \Carbon\Carbon::parse($item->fecha_pago)->format('d M, Y') }}
                                </span>
                            </div>

                            <div class="absolute right-2 opacity-0 group-hover:opacity-100 transition-opacity text-gray-400">
                                <x-filament::icon icon="heroicon-m-chevron-right" class="w-4 h-4" />
                            </div>

                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="flex flex-col items-center justify-center p-8 bg-white border border-gray-200 border-dashed rounded-xl dark:bg-gray-800 dark:border-gray-700">
                <div class="p-3 bg-gray-50 rounded-full dark:bg-gray-700 mb-3">
                    <x-filament::icon icon="heroicon-o-currency-dollar" class="w-8 h-8 text-gray-400 dark:text-gray-500" />
                </div>
                <p class="text-sm font-medium text-gray-900 dark:text-white">No hay pagos registrados</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Los pagos aparecerán aquí organizados por mes.</p>
            </div>
        @endforelse
    </div>
    
    <x-filament-actions::modals />
</div>