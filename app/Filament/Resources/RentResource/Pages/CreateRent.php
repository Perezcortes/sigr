<?php

namespace App\Filament\Resources\RentResource\Pages;

use App\Filament\Resources\RentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateRent extends CreateRecord
{
    protected static string $resource = RentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}