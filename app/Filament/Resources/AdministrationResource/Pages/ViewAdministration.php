<?php

namespace App\Filament\Resources\AdministrationResource\Pages;

use App\Filament\Resources\AdministrationResource;
use App\Models\Rent;
use App\Support\Filament\AdministrationTabs;
use Filament\Actions;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class ViewAdministration extends EditRecord
{
    protected static string $resource = AdministrationResource::class;

    protected static ?string $title = 'Administración de Inmueble';

    // Ocultamos los botones globales de "Guardar" del pie de página
    protected function getFormActions(): array
    {
        return [];
    }

    public function resolveRecord(string|int $key): Model
    {
        $record = Rent::findByHash($key);

        if (! $record) {
            abort(404);
        }

        return $record;
    }

    // Botón para volver al listado
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('volver')
                ->label('Volver al listado')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn () => AdministrationResource::getUrl('index')),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                AdministrationTabs::make($this->record),
            ]);
    }
}
