<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateSale extends CreateRecord
{
    protected static string $resource = SaleResource::class;

    // Cuando se crea la Venta, redirigir de inmediato a la vista de "Edición" 
    // donde están todas las pestañas (Comprador, Vendedor, Operación, Hipoteca)
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
}