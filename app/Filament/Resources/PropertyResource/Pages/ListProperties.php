<?php

namespace App\Filament\Resources\PropertyResource\Pages;

use App\Filament\Resources\PropertyResource;
use App\Models\Property;
use App\Models\User;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\MaxWidth; 
use Illuminate\Database\Eloquent\Builder;

class ListProperties extends ListRecords
{
    protected static string $resource = PropertyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('crear_propiedad')
                ->label('Crear Propiedad')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->modalHeading('Crear Nueva Propiedad')
                ->modalWidth('md')
                ->form([
                    Forms\Components\Select::make('user_id')
                        ->label('Propietario')
                        ->relationship('user', 'name', modifyQueryUsing: fn (Builder $query) => $query->where('is_owner', true))
                        ->getOptionLabelFromRecordUsing(fn (User $record) => $record->name . ' (' . $record->email . ')')
                        ->searchable(['name', 'email'])
                        ->preload()
                        ->required(),
                ])
                ->action(function (array $data) {
                    // Crear la Property con el propietario seleccionado
                    $property = Property::create([
                        'user_id' => $data['user_id'],
                        'estatus' => 'disponible',
                    ]);

                    // Redirigir a EditProperty
                    return redirect(PropertyResource::getUrl('edit', ['record' => $property->id]));
                }),
        ];
    }

    public function getMaxContentWidth(): \Filament\Support\Enums\MaxWidth | string | null
    {
        return 'full'; 
    }
}
