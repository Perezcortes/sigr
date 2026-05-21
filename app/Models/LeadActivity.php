<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadActivity extends Model
{
    protected $table = 'lead_activities';

    protected $fillable = [
        'lead_id',
        'user_id',
        'fecha',
        'hora',
        'descripcion',
        'completada',
    ];

    protected $casts = [
        'fecha'      => 'date',
        'completada' => 'boolean',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Admin: todas. Gerente: su oficina. Agente/Asesor: solo las propias.
     *
     * @param  Builder<LeadActivity>  $query
     * @return Builder<LeadActivity>
     */
    public function scopeVisibleToAgendaUser(Builder $query, ?User $user = null): Builder
    {
        $user ??= auth()->user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasRole('Administrador')) {
            return $query;
        }

        if ($user->hasRole('Gerente')) {
            if (blank($user->office_id)) {
                return $query->where('user_id', $user->id);
            }

            return $query->whereHas(
                'user',
                fn (Builder $agentQuery) => $agentQuery->where('office_id', $user->office_id)
            );
        }

        return $query->where('user_id', $user->id);
    }

    public function isVisibleToAgendaUser(?User $viewer = null): bool
    {
        $viewer ??= auth()->user();

        if (! $viewer) {
            return false;
        }

        return static::query()
            ->visibleToAgendaUser($viewer)
            ->whereKey($this->getKey())
            ->exists();
    }

    /**
     * Fecha y hora combinadas como string legible (ej. "Lun 16 May · 10:00")
     */
    public function getFechaHoraAttribute(): string
    {
        $fecha = $this->fecha ? $this->fecha->translatedFormat('D d M') : '—';
        $hora  = $this->hora ?? '';

        return $hora ? "{$fecha} · {$hora}" : $fecha;
    }
}
