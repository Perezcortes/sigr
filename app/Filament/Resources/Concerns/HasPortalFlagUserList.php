<?php

namespace App\Filament\Resources\Concerns;

use App\Support\Filament\ScopesByOfficeAndAdvisor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait HasPortalFlagUserList
{
    abstract protected static function portalFlagColumn(): string;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $query->where(static::portalFlagColumn(), true);
        ScopesByOfficeAndAdvisor::applyPortalClientUsersConstraints($query, auth()->user());

        return $query;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->hasAnyRole(['Administrador', 'Gerente', 'Agente', 'Asesor']);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }
}
