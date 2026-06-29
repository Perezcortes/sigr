<x-filament-panels::page>
    @include('filament.pages.partials.agenda-calendar-scripts')

    {{-- Barra: vista + filtros --}}
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:flex-wrap">
        <div class="inline-flex rounded-lg border border-gray-200 dark:border-gray-700 p-0.5 bg-white dark:bg-gray-800">
            <button
                type="button"
                wire:click="setVista('lista')"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-sm font-medium transition-colors
                    {{ $vista === 'lista'
                        ? 'bg-[#161848] text-white dark:bg-[#26cad3] dark:text-gray-900'
                        : 'text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700' }}"
            >
                <x-heroicon-o-bars-3-bottom-left class="w-4 h-4" />
                Lista
            </button>
            <button
                type="button"
                wire:click="setVista('calendario')"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-sm font-medium transition-colors
                    {{ $vista === 'calendario'
                        ? 'bg-[#161848] text-white dark:bg-[#26cad3] dark:text-gray-900'
                        : 'text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700' }}"
            >
                <x-heroicon-o-calendar-days class="w-4 h-4" />
                Calendario
            </button>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Mostrar:</span>
            @foreach(['pendientes' => 'Pendientes', 'completadas' => 'Realizadas', 'todas' => 'Todas'] as $valor => $etiqueta)
                <button
                    type="button"
                    wire:click="setFiltro('{{ $valor }}')"
                    class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors
                        {{ $filtro === $valor
                            ? 'bg-[#161848] text-white dark:bg-[#26cad3] dark:text-gray-900'
                            : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700' }}"
                >
                    {{ $etiqueta }}
                </button>
            @endforeach
        </div>

        <span class="sm:ml-auto text-xs text-gray-400 dark:text-gray-500">
            {{ $actividades->count() }} {{ $actividades->count() === 1 ? 'actividad' : 'actividades' }}
        </span>
    </div>

    {{-- Calendario siempre en DOM (Livewire no ejecuta scripts en partials nuevos) --}}
    <div
        wire:key="agenda-calendario-{{ $filtro }}"
        @class(['hidden' => $vista !== 'calendario'])
    >
        @include('filament.pages.partials.agenda-calendario', ['eventos' => $eventos])
    </div>

    <div @class(['hidden' => $vista !== 'lista'])>
        @include('filament.pages.partials.agenda-lista', [
            'actividades' => $actividades,
            'verEquipo' => $verEquipo,
        ])
    </div>
</x-filament-panels::page>
