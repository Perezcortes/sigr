<?php

namespace App\Filament\Resources\AdministrationResource\Pages;

use App\Filament\Resources\AdministrationResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ListAdministrations extends ListRecords
{
    protected static string $resource = AdministrationResource::class;

    public function getTabs(): array
    {
        return [
            'activas' => Tab::make('Activas')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('estatus', 'activa')),

            'por_vencer' => Tab::make('Por vencer')
                ->badge(fn (): int => AdministrationResource::getEloquentQuery()
                    ->where('estatus', 'activa')
                    ->whereDate('end_date', '>=', Carbon::today())
                    ->whereDate('end_date', '<=', Carbon::today()->copy()->addDays(30))
                    ->count())
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('estatus', 'activa')
                    ->whereDate('end_date', '>=', Carbon::today())
                    ->whereDate('end_date', '<=', Carbon::today()->copy()->addDays(30))),

            'vencidas' => Tab::make('Vencidas')
                ->badge(fn (): int => AdministrationResource::getEloquentQuery()
                    ->where(function (Builder $query): void {
                        $query->where('estatus', 'vencida')
                            ->orWhere(function (Builder $subQuery): void {
                                $subQuery->where('estatus', 'activa')
                                    ->whereDate('end_date', '<', Carbon::today());
                            });
                    })
                    ->count())
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where(function (Builder $query): void {
                        $query->where('estatus', 'vencida')
                            ->orWhere(function (Builder $subQuery): void {
                                $subQuery->where('estatus', 'activa')
                                    ->whereDate('end_date', '<', Carbon::today());
                            });
                    })),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
