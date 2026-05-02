<?php

namespace App\Filament\Resources\RentResource\Pages;

use App\Filament\Resources\RentResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ListRents extends ListRecords
{
    protected static string $resource = RentResource::class;

    public function getTabs(): array
    {
        return [
            'en_proceso' => Tab::make('En proceso')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('estatus', [
                    'nueva',
                    'documentacion',
                    'analisis',
                ])),

            'activas' => Tab::make('Activas')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('estatus', 'activa')),

            'por_vencer' => Tab::make('Por vencer')
                ->badge(fn (): int => RentResource::getEloquentQuery()
                    ->where('estatus', 'activa')
                    ->whereDate('end_date', '>=', Carbon::today())
                    ->whereDate('end_date', '<=', Carbon::today()->copy()->addDays(30))
                    ->count())
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('estatus', 'activa')
                    ->whereDate('end_date', '>=', Carbon::today())
                    ->whereDate('end_date', '<=', Carbon::today()->copy()->addDays(30))),

            'vencidas' => Tab::make('Vencidas')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('estatus', 'vencida')),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
