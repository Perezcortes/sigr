<?php

namespace App\Filament\Resources\RentResource\Pages;

use App\Filament\Resources\RentResource;
use App\Models\Rent;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRent extends EditRecord
{
    protected static string $resource = RentResource::class;

    /**
     * Resuelve el record desde el parámetro de la ruta usando hash
     */
    public function resolveRecord(string|int $key): \Illuminate\Database\Eloquent\Model
    {
        // Intentar encontrar por hash primero
        $record = Rent::findByHash($key);
        
        // Si no se encuentra por hash, intentar por ID (compatibilidad hacia atrás)
        if (!$record && is_numeric($key)) {
            $record = Rent::find($key);
        }

        if (!$record) {
            abort(404);
        }

        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
