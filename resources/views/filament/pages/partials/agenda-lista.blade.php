@if($actividades->isEmpty())
    <div class="flex flex-col items-center justify-center py-16 text-center">
        <div class="p-4 bg-gray-100 dark:bg-gray-800 rounded-full mb-4">
            <x-heroicon-o-calendar-days class="w-10 h-10 text-gray-400" />
        </div>
        <h3 class="text-base font-semibold text-gray-700 dark:text-gray-300 mb-1">Sin actividades</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Agrega seguimientos desde el perfil de cada interesado.
        </p>
    </div>
@else
    @php
        $grouped  = $actividades->groupBy(fn ($a) => $a->fecha->format('Y-m-d'));
        $today    = now()->format('Y-m-d');
        $tomorrow = now()->addDay()->format('Y-m-d');
        $authId   = auth()->id();
    @endphp

    @foreach($grouped as $fecha => $items)
        @php
            $fechaCarbon = \Carbon\Carbon::parse($fecha);
            $esHoy      = $fecha === $today;
            $esMañana   = $fecha === $tomorrow;
            $esPasada   = $fechaCarbon->isPast() && ! $esHoy;

            $labelFecha = match(true) {
                $esHoy    => 'Hoy · ' . $fechaCarbon->translatedFormat('l d \d\e F'),
                $esMañana => 'Mañana · ' . $fechaCarbon->translatedFormat('l d \d\e F'),
                default   => $fechaCarbon->translatedFormat('l d \d\e F \d\e Y'),
            };
        @endphp

        <div class="mb-6">
            <div class="flex items-center gap-2 mb-2">
                <span class="text-xs font-bold uppercase tracking-widest
                    {{ $esHoy ? 'text-[#26cad3]' : ($esPasada ? 'text-red-400' : 'text-gray-500 dark:text-gray-400') }}">
                    {{ $labelFecha }}
                </span>
                @if($esPasada)
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-red-100 text-red-600 dark:bg-red-900/40 dark:text-red-400">
                        Vencida
                    </span>
                @endif
                <div class="flex-1 h-px bg-gray-200 dark:bg-gray-700"></div>
            </div>

            <div class="space-y-2">
                @foreach($items as $actividad)
                    @php
                        $esMia = $actividad->user_id === $authId;
                        $puedeToggle = auth()->user()->hasRole('Administrador')
                            || (auth()->user()->hasRole('Gerente') && $actividad->user && $actividad->user->office_id === auth()->user()->office_id)
                            || $esMia;
                    @endphp

                    <div class="flex items-start gap-3 bg-white dark:bg-gray-800 rounded-xl
                        border-l-4 {{ $esMia ? 'border-l-[#26cad3]' : 'border-l-gray-300 dark:border-l-gray-600' }}
                        border border-gray-200 dark:border-gray-700
                        {{ $actividad->completada ? 'opacity-60' : '' }}
                        {{ ($esPasada && ! $actividad->completada) ? 'border-red-200 dark:border-red-800/50' : '' }}
                        p-4 shadow-sm">

                        @if($puedeToggle)
                            <button
                                type="button"
                                wire:click="marcarCompletada({{ $actividad->id }})"
                                title="{{ $actividad->completada ? 'Marcar como pendiente' : 'Marcar como realizada' }}"
                                class="mt-0.5 flex-shrink-0 w-5 h-5 rounded-full border-2 flex items-center justify-center transition-colors
                                    {{ $actividad->completada
                                        ? 'bg-[#26cad3] border-[#26cad3] text-white'
                                        : 'border-gray-300 dark:border-gray-600 hover:border-[#26cad3]' }}"
                            >
                                @if($actividad->completada)
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                @endif
                            </button>
                        @else
                            <div class="mt-0.5 flex-shrink-0 w-5 h-5 rounded-full border-2 border-gray-200 dark:border-gray-600
                                {{ $actividad->completada ? 'bg-gray-200 dark:bg-gray-600' : '' }}">
                            </div>
                        @endif

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                @if($actividad->hora)
                                    <span class="text-xs font-bold text-[#161848] dark:text-[#26cad3] bg-[#161848]/10 dark:bg-[#26cad3]/10 px-2 py-0.5 rounded">
                                        {{ $actividad->hora }}
                                    </span>
                                @endif

                                @if($actividad->lead)
                                    <a
                                        href="{{ \App\Filament\Resources\LeadResource::getUrl('edit', ['record' => $actividad->lead_id]) }}"
                                        class="text-sm font-semibold text-gray-800 dark:text-white hover:text-[#26cad3] transition-colors truncate"
                                    >
                                        {{ $actividad->lead->nombre }}
                                    </a>
                                @endif

                                @if($verEquipo && ! $esMia && $actividad->user)
                                    <span class="text-xs text-gray-400 dark:text-gray-500 bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded-full">
                                        {{ $actividad->user->name }}
                                    </span>
                                @elseif($verEquipo && $esMia)
                                    <span class="text-xs text-[#26cad3] bg-[#26cad3]/10 px-2 py-0.5 rounded-full font-medium">
                                        Yo
                                    </span>
                                @endif
                            </div>

                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300 {{ $actividad->completada ? 'line-through text-gray-400' : '' }}">
                                {{ $actividad->descripcion }}
                            </p>
                        </div>

                        <div class="flex-shrink-0">
                            @if($actividad->completada)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                    Realizada
                                </span>
                            @elseif($esPasada)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400">
                                    Vencida
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                                    Pendiente
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
@endif
