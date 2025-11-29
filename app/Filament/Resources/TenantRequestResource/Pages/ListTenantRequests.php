<?php

namespace App\Filament\Resources\TenantRequestResource\Pages;

use App\Filament\Resources\TenantRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTenantRequests extends ListRecords
{
    protected static string $resource = TenantRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}