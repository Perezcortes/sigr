<?php

namespace App\Support\Filament;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Alcance unificado: Administrador (todo), Gerente (oficina), Asesor (asignación / misma oficina según recurso).
 */
final class ScopesByOfficeAndAdvisor
{
    /**
     * Listado de ventas: creador en la misma oficina (gerente) o solo propias (asesor).
     *
     * @param  Builder<\App\Models\Sale>  $query
     * @return Builder<\App\Models\Sale>
     */
    public static function scopeSalesForFilament(Builder $query, User $user): Builder
    {
        if ($user->hasRole('Administrador')) {
            return $query;
        }

        if ($user->hasRole('Gerente')) {
            return $query->whereHas('user', function (Builder $q) use ($user): void {
                $q->where('office_id', $user->office_id);
            });
        }

        if ($user->hasRole('Agente')) {
            return $query->where('user_id', $user->id);
        }

        return $query;
    }

    /**
     * Listado de rentas: gerente por office_id; asesor por asignación y oficina (o sin office en legacy).
     *
     * @param  Builder<\App\Models\Rent>  $query
     * @return Builder<\App\Models\Rent>
     */
    public static function scopeRentListForFilament(Builder $query, User $user): Builder
    {
        if ($user->hasRole('Administrador')) {
            return $query;
        }

        if ($user->hasRole('Gerente')) {
            return $query->where($query->qualifyColumn('office_id'), $user->office_id);
        }

        if ($user->hasRole('Agente')) {
            return $query->where($query->qualifyColumn('asesor_id'), $user->id)
                ->where(function (Builder $q) use ($user): void {
                    $q->where($q->qualifyColumn('office_id'), $user->office_id)
                        ->orWhereNull($q->qualifyColumn('office_id'));
                });
        }

        return $query;
    }

    /**
     * Select de inquilino/propietario en el formulario de renta.
     *
     * @param  Builder<\App\Models\Tenant>|Builder<\App\Models\Owner>  $query
     * @return Builder<\App\Models\Tenant>|Builder<\App\Models\Owner>
     */
    public static function scopeTenantOwnerRelationshipForRentForm(Builder $query, User $user): Builder
    {
        if ($user->hasRole('Administrador')) {
            return $query;
        }

        if ($user->hasRole('Gerente')) {
            return $query->whereHas('asesor', function (Builder $a) use ($user): void {
                $a->where('office_id', $user->office_id);
            });
        }

        if ($user->hasRole('Agente')) {
            return $query->where($query->qualifyColumn('asesor_id'), $user->id);
        }

        return $query;
    }

    /**
     * Índice Filament de inquilinos o arrendadores (sin filtro de banderas portal).
     *
     * @param  Builder<\App\Models\Tenant>|Builder<\App\Models\Owner>  $query
     * @return Builder<\App\Models\Tenant>|Builder<\App\Models\Owner>
     */
    public static function scopeTenantOwnerIndexForFilament(Builder $query, User $user): Builder
    {
        if ($user->hasRole('Administrador')) {
            return $query;
        }

        if ($user->hasRole('Gerente')) {
            return $query->whereHas('asesor', function (Builder $q) use ($user): void {
                $q->where('office_id', $user->office_id);
            });
        }

        if ($user->hasRole('Agente')) {
            return $query->where('asesor_id', $user->id);
        }

        return $query;
    }

    /**
     * Expedientes CRM buyer/seller en creación de venta (tabla buyers o sellers).
     */
    public static function scopeBuyersSellersForSaleForm(Builder $query, User $user): void
    {
        if ($user->hasRole('Administrador')) {
            return;
        }

        if ($user->hasRole('Gerente')) {
            $query->whereHas('asesor', function (Builder $a) use ($user): void {
                $a->where('office_id', $user->office_id);
            });

            return;
        }

        if ($user->hasRole('Agente')) {
            $query->where(function (Builder $q) use ($user): void {
                $q->where($q->qualifyColumn('user_id'), $user->id)
                    ->orWhereNull($q->qualifyColumn('user_id'));
            });
        }
    }

    /**
     * Restringe un query de usuarios (portal comprador/vendedor) por rol.
     *
     * @param  Builder<\App\Models\User>  $query
     */
    public static function applyPortalClientUsersConstraints(Builder $query, User $user): void
    {
        if ($user->hasRole('Administrador')) {
            return;
        }

        if ($user->hasRole('Gerente')) {
            $query->where('office_id', $user->office_id);

            return;
        }

        if ($user->hasRole('Agente')) {
            $query->where('asesor_id', $user->id);
        }
    }
}
