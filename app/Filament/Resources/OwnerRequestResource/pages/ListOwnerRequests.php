<?php

namespace App\Filament\Resources\OwnerRequestResource\Pages;

use App\Filament\Resources\OwnerRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOwnerRequests extends ListRecords
{
    protected static string $resource = OwnerRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}