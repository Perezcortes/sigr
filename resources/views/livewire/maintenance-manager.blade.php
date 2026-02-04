<div class="space-y-6">
    
    <div class="w-full">
        {{ $this->crearTicketAction }}
    </div>

    <div class="space-y-3">
        <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider">Reportes Activos</h3>
        
        @forelse($activeTickets as $ticket)
            <div x-data="{ open: false }" class="bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700 overflow-hidden">
                
                <button @click="open = !open" 
                        class="report-row flex items-center justify-between w-full p-4 transition rounded-xl">
                    
                    <div class="flex items-center gap-3">
                        <div class="p-2 rounded-full {{ $ticket->estatus == 'en_proceso' ? 'bg-blue-100 text-blue-600' : 'bg-red-100 text-red-600' }}">
                            <x-filament::icon icon="heroicon-m-wrench-screwdriver" class="w-5 h-5" />
                        </div>
                        
                        <div class="text-left">
                            <p class="font-bold text-gray-800 dark:text-white text-base">
                                {{ $ticket->titulo }}
                            </p>
                            <p class="text-xs text-gray-500">
                                {{ $ticket->created_at->format('h:i A') }} • {{ $ticket->created_at->format('d M') }}
                            </p>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-2">
                         <span class="text-xs font-bold px-2 py-1 rounded capitalize
                            {{ $ticket->estatus == 'en_proceso' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700' }}">
                            {{ str_replace('_', ' ', $ticket->estatus) }}
                        </span>
                        <svg class="w-5 h-5 text-gray-400 transform transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </div>
                </button>

                <div x-show="open" x-collapse class="border-t border-gray-100 dark:border-gray-700 p-4 bg-gray-50 dark:bg-gray-900/50">
                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-3">{{ $ticket->descripcion }}</p>
                    
                    <div class="flex justify-end">
                        <button wire:click="mountAction('editarTicket', { record: {{ $ticket->id }} })" 
                                class="text-sm text-primary-600 font-bold hover:underline flex items-center gap-1 dark:text-primary-400">
                            Ver detalles y gestionar
                            <x-filament::icon icon="heroicon-m-arrow-right" class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center p-6 bg-gray-50 border border-dashed rounded-xl dark:bg-gray-800/50 dark:border-gray-700">
                <p class="text-gray-400 text-sm">No hay reportes activos.</p>
            </div>
        @endforelse
    </div>

    <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
        <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-3">Bitácora (Historial)</h3>
        
        <div class="bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 divide-y divide-gray-200 dark:divide-gray-700 overflow-hidden">
            @forelse($completedTickets as $ticket)
                <div class="report-row p-3 flex items-center justify-between opacity-75 hover:opacity-100 transition">
                    
                    <div class="flex items-center gap-3">
                        <x-filament::icon icon="heroicon-m-check-circle" class="w-5 h-5 text-green-500" />
                        <div>
                            <p class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ $ticket->titulo }}</p>
                            <p class="text-xs text-gray-400">{{ $ticket->updated_at->format('d M, Y') }}</p>
                        </div>
                    </div>
                    <span class="text-xs font-bold text-green-700 bg-green-100 px-2 py-1 rounded dark:bg-green-900/30 dark:text-green-400">Terminado</span>
                </div>
            @empty
                <div class="p-4 text-center text-xs text-gray-400">
                    No hay reportes terminados aún.
                </div>
            @endforelse
        </div>
    </div>

    <x-filament-actions::modals />
</div>