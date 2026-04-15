<?php

namespace App\Filament\Resources\PropertyResource\Pages;

use App\Filament\Resources\PropertyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\MaxWidth;

class ListProperties extends ListRecords
{
    protected static string $resource = PropertyResource::class;

    protected function getFilteredOwnerId(): ?int
    {
        $ownerId = data_get($this->tableFilters ?? [], 'user_id.value')
            ?? request()->query('tableFilters.user_id.value');

        return filled($ownerId) ? (int) $ownerId : null;
    }

    protected function getHeaderActions(): array
    {
        $userIdFilter = $this->getFilteredOwnerId();

        return [
            Actions\Action::make('crear_propiedad')
                ->label('Crear Propiedad')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->url(fn (): string => filled($userIdFilter)
                    ? PropertyResource::getUrl('create') . '?user_id=' . $userIdFilter
                    : PropertyResource::getUrl('create')
                ),
        ];
    }

    public function getMaxContentWidth(): \Filament\Support\Enums\MaxWidth | string | null
    {
        return 'full'; 
    }
}
