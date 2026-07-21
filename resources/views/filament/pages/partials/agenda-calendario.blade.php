{{-- Siempre en el DOM; la visibilidad la controla la vista padre --}}
<div
    id="agenda-calendar-root"
    class="agenda-calendar-wrap rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 sm:p-5 shadow-sm overflow-hidden"
>
    <script type="application/json" id="agenda-events-json">{!! json_encode($eventos, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>

    @if(count($eventos) === 0)
        <div id="agenda-calendar-empty" class="flex flex-col items-center justify-center py-16 text-center">
            <x-heroicon-o-calendar-days class="w-10 h-10 text-gray-400 mb-3" />
            <p class="text-sm text-gray-500 dark:text-gray-400">No hay actividades para mostrar en el calendario con este filtro.</p>
        </div>
        <div id="agenda-fullcalendar" class="hidden w-full"></div>
    @else
        <div id="agenda-calendar-empty" class="hidden"></div>
        <div id="agenda-fullcalendar" class="w-full min-h-[680px]"></div>
    @endif

    <p id="agenda-calendar-error" class="hidden mt-3 text-sm text-amber-600 dark:text-amber-400"></p>
</div>

<div
    id="agenda-cal-tooltip"
    class="hidden fixed z-[200] max-w-xs rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-900 shadow-xl p-3 text-sm pointer-events-none"
    role="tooltip"
></div>
