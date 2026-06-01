<?php

namespace App\Filament\Pages;

use App\Models\LeadActivity;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class Agenda extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Agenda';
    protected static ?string $navigationGroup = 'Dashboard';
    protected static ?string $title           = 'Mi Agenda';
    protected static ?int    $navigationSort  = 0;

    protected static string $view = 'filament.pages.agenda';

    /** Filtro activo: 'pendientes' | 'completadas' | 'todas' */
    public string $filtro = 'pendientes';

    public function setFiltro(string $filtro): void
    {
        $this->filtro = $filtro;
    }

    /**
     * Devuelve la colección filtrada según el rol del usuario:
     *  - Admin   → todas las actividades
     *  - Gerente → actividades de usuarios de su misma oficina
     *  - Agente  → solo las propias
     */
    public function getActividades(): Collection
    {
        $user = auth()->user();

        $query = LeadActivity::with(['lead', 'user'])
            ->when($this->filtro === 'pendientes',  fn ($q) => $q->where('completada', false))
            ->when($this->filtro === 'completadas', fn ($q) => $q->where('completada', true));

        if ($user->hasRole('Administrador')) {
            // Ve todo, sin restricción
        } elseif ($user->hasRole('Gerente')) {
            // Solo actividades de usuarios de su misma oficina
            $query->whereHas('user', fn ($q) => $q->where('office_id', $user->office_id));
        } else {
            // Agente: solo las propias
            $query->where('user_id', $user->id);
        }

        return $query
            ->orderBy('fecha')
            ->orderBy('hora')
            ->get();
    }

    /**
     * Indica si una actividad pertenece al usuario autenticado.
     * Usado en la vista para resaltar las propias cuando el Gerente ve al equipo.
     */
    public function esMia(LeadActivity $actividad): bool
    {
        return $actividad->user_id === auth()->id();
    }

    /**
     * Indica si el usuario actual puede ver actividades de otras personas
     * (para mostrar la columna "Agente" en la vista).
     */
    public function verEquipo(): bool
    {
        return auth()->user()->hasAnyRole(['Administrador', 'Gerente']);
    }

    public function marcarCompletada(int $id): void
    {
        $activity = LeadActivity::find($id);

        if (! $activity) {
            return;
        }

        $user = auth()->user();

        // Admin puede marcar cualquiera; Gerente puede marcar las de su oficina; Agente solo las propias
        $puedeEditar = $user->hasRole('Administrador')
            || ($user->hasRole('Gerente') && $activity->user && $activity->user->office_id === $user->office_id)
            || $activity->user_id === $user->id;

        if (! $puedeEditar) {
            return;
        }

        $activity->update(['completada' => ! $activity->completada]);
    }

    public function getViewData(): array
    {
        return [
            'actividades' => $this->getActividades(),
            'filtro'      => $this->filtro,
            'verEquipo'   => $this->verEquipo(),
        ];
    }
}
