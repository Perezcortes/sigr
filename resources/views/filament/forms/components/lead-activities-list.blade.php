<div class="space-y-3" wire:key="seguimiento-activities-list-{{ $listKey }}">
    @php
        $activities = \App\Filament\Resources\LeadResource::orderedActivitiesForList($getRecord());
    @endphp

    @forelse($activities as $activity)
        <div
            wire:key="lead-activity-{{ $activity->id }}"
            @class([
                'rounded-lg border p-3 text-sm shadow-sm',
                'border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900' => ! $activity->completada,
                'border-gray-100 bg-gray-50 opacity-75 dark:border-gray-800 dark:bg-gray-800/60' => $activity->completada,
            ])
        >
            <div class="flex flex-wrap items-start gap-3">
                <label
                    class="mt-0.5 flex shrink-0 cursor-pointer items-center"
                    title="{{ $activity->completada ? 'Marcar como pendiente' : 'Marcar como realizada' }}"
                >
                    <input
                        type="checkbox"
                        id="lead-activity-checkbox-{{ $activity->id }}"
                        class="fi-checkbox-input size-4 rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500 checked:bg-primary-600 checked:border-primary-600 dark:border-gray-600 dark:bg-gray-900 dark:checked:border-primary-500 dark:checked:bg-primary-500"
                        @checked($activity->completada)
                        wire:click="toggleActividadCompletada({{ $activity->id }})"
                        wire:loading.attr="disabled"
                        wire:target="toggleActividadCompletada"
                    />
                </label>

                <div class="min-w-0 flex-1">
                    <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                        <span class="font-semibold text-gray-900 dark:text-gray-100">
                            {{ $activity->fecha->format('d/m/Y') }}
                            @if ($activity->hora)
                                <span class="font-normal text-gray-500">· {{ $activity->hora }}</span>
                            @endif
                        </span>

                        @if ($activity->completada)
                            <span class="inline-flex items-center rounded-md bg-success-50 px-2 py-0.5 text-xs font-medium text-success-700 ring-1 ring-inset ring-success-600/20 dark:bg-success-400/10 dark:text-success-400">
                                Realizada
                            </span>
                        @else
                            <span class="inline-flex items-center rounded-md bg-warning-50 px-2 py-0.5 text-xs font-medium text-warning-700 ring-1 ring-inset ring-warning-600/20 dark:bg-warning-400/10 dark:text-warning-400">
                                Pendiente
                            </span>
                        @endif
                    </div>

                    <p class="text-gray-700 dark:text-gray-200">{{ $activity->descripcion }}</p>

                    @if ($activity->user)
                        <p class="mt-2 text-xs text-gray-500">{{ $activity->user->name }}</p>
                    @endif
                </div>

                <div class="flex shrink-0 items-center gap-1" wire:key="lead-activity-actions-{{ $activity->id }}">
                    <x-filament::icon-button
                        color="gray"
                        icon="heroicon-m-pencil-square"
                        label="Editar"
                        wire:click="mountAction('editActividad', { activityId: {{ $activity->id }} })"
                    />

                    <x-filament::icon-button
                        color="danger"
                        icon="heroicon-m-trash"
                        label="Eliminar"
                        wire:click="mountAction('deleteActividad', { activityId: {{ $activity->id }} })"
                    />
                </div>
            </div>
        </div>
    @empty
        <p class="text-sm text-gray-500 dark:text-gray-400">No hay actividades registradas.</p>
    @endforelse
</div>
