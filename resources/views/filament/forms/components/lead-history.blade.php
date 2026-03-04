<div class="space-y-4">
    @php
        $historial = $getRecord()->historial_acciones ?? [];
        $historial = array_reverse($historial); // Mostrar los más nuevos arriba
    @endphp

    @forelse($historial as $item)
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3 text-sm shadow-sm">
            <span class="text-xs text-gray-500 font-bold block mb-1">{{ $item['fecha'] }}</span>
            <span class="text-gray-700 dark:text-gray-200">{{ $item['accion'] }}</span>
        </div>
    @empty
        <p class="text-gray-500 text-sm">No hay acciones registradas.</p>
    @endforelse
</div>