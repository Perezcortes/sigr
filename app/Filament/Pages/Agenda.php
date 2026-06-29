<?php

namespace App\Filament\Pages;

use App\Filament\Resources\LeadResource;
use App\Models\LeadActivity;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Agenda extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Agenda';

    protected static ?string $navigationGroup = 'Dashboard';

    protected static ?int $navigationSort = 0;

    protected static string $view = 'filament.pages.agenda';

    /** Filtro activo: 'pendientes' | 'completadas' | 'todas' */
    public string $filtro = 'pendientes';

    /** Vista activa: 'lista' | 'calendario' */
    public string $vista = 'lista';

    public function setFiltro(string $filtro): void
    {
        if (! in_array($filtro, ['pendientes', 'completadas', 'todas'], true)) {
            return;
        }

        $this->filtro = $filtro;

        if ($this->vista === 'calendario') {
            $this->dispatch('agenda-calendar-refresh');
        }
    }

    public function setVista(string $vista): void
    {
        if (! in_array($vista, ['lista', 'calendario'], true)) {
            return;
        }

        $this->vista = $vista;

        if ($vista === 'calendario') {
            $this->dispatch('agenda-calendar-refresh');
        }
    }

    /**
     * @return Builder<LeadActivity>
     */
    protected function actividadesQuery(): Builder
    {
        return LeadActivity::query()
            ->visibleToAgendaUser()
            ->with(['lead', 'user'])
            ->when($this->filtro === 'pendientes', fn ($q) => $q->where('completada', false))
            ->when($this->filtro === 'completadas', fn ($q) => $q->where('completada', true));
    }

    public function getActividades(): Collection
    {
        return $this->actividadesQuery()
            ->orderBy('fecha')
            ->orderBy('hora')
            ->get();
    }

    /**
     * Eventos para FullCalendar.
     *
     * @return list<array<string, mixed>>
     */
    public function getEventosCalendario(): array
    {
        $authId = auth()->id();
        $today = now()->startOfDay();

        return $this->actividadesQuery()
            ->orderBy('fecha')
            ->orderBy('hora')
            ->get()
            ->map(function (LeadActivity $actividad) use ($authId, $today): array {
                $esMia = $actividad->user_id === $authId;
                $vencida = ! $actividad->completada
                    && $actividad->fecha->copy()->startOfDay()->lt($today);

                $tituloLead = $actividad->lead?->nombre ?? 'Sin interesado';
                $titulo = Str::limit($tituloLead.' — '.$actividad->descripcion, 55);

                $start = $this->fechaHoraInicio($actividad);
                $end = $this->fechaHoraFin($actividad, $start);
                $allDay = $actividad->hora === null || $actividad->hora === '';

                return [
                    'id' => (string) $actividad->id,
                    'title' => $titulo,
                    'start' => $start,
                    'end' => $allDay ? null : $end,
                    'allDay' => $allDay,
                    'url' => $actividad->lead_id
                        ? LeadResource::getUrl('edit', ['record' => $actividad->lead_id])
                        : null,
                    'backgroundColor' => $this->colorEvento($actividad, $esMia, $vencida),
                    'borderColor' => $this->colorEvento($actividad, $esMia, $vencida),
                    'textColor' => '#ffffff',
                    'extendedProps' => [
                        'completada' => $actividad->completada,
                        'esMia' => $esMia,
                        'vencida' => $vencida,
                        'hora' => $actividad->hora,
                        'agente' => $actividad->user?->name,
                        'lead' => $tituloLead,
                        'descripcion' => $actividad->descripcion,
                    ],
                ];
            })
            ->values()
            ->all();
    }

    protected function fechaHoraInicio(LeadActivity $actividad): string
    {
        $fecha = $actividad->fecha->format('Y-m-d');

        if (blank($actividad->hora)) {
            return $fecha;
        }

        $hora = $this->normalizarHora((string) $actividad->hora);

        return "{$fecha}T{$hora}";
    }

    protected function fechaHoraFin(LeadActivity $actividad, string $start): string
    {
        if (blank($actividad->hora)) {
            return $actividad->fecha->format('Y-m-d');
        }

        try {
            return Carbon::parse($start)->addHour()->format('Y-m-d\TH:i:s');
        } catch (\Throwable) {
            return $start;
        }
    }

    protected function normalizarHora(string $hora): string
    {
        $hora = trim($hora);

        if (preg_match('/^\d{2}:\d{2}$/', $hora)) {
            return $hora.':00';
        }

        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $hora)) {
            return $hora;
        }

        return '09:00:00';
    }

    protected function colorEvento(LeadActivity $actividad, bool $esMia, bool $vencida): string
    {
        if ($actividad->completada) {
            return '#16a34a';
        }

        if ($vencida) {
            return '#dc2626';
        }

        return $esMia ? '#26cad3' : '#161848';
    }

    public function esMia(LeadActivity $actividad): bool
    {
        return $actividad->user_id === auth()->id();
    }

    public function puedeEditar(LeadActivity $actividad): bool
    {
        return $actividad->isVisibleToAgendaUser() && (
            auth()->user()->hasRole('Administrador')
            || auth()->user()->hasRole('Gerente')
            || $actividad->user_id === auth()->id()
        );
    }

    public function verEquipo(): bool
    {
        return auth()->user()->hasAnyRole(['Administrador', 'Gerente']);
    }

    public function marcarCompletada(int $id): void
    {
        $activity = $this->actividadesQuery()->whereKey($id)->first();

        if (! $activity || ! $this->puedeEditar($activity)) {
            return;
        }

        $activity->update(['completada' => ! $activity->completada]);
    }

    public function getTitle(): string
    {
        return $this->verEquipo() ? 'Agenda del equipo' : 'Mi Agenda';
    }

    public function getViewData(): array
    {
        return [
            'actividades' => $this->getActividades(),
            'eventos' => $this->getEventosCalendario(),
            'filtro' => $this->filtro,
            'vista' => $this->vista,
            'verEquipo' => $this->verEquipo(),
        ];
    }
}
