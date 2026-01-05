<?php

namespace App\Filament\Resources\PropertyResource\Pages;

use App\Filament\Resources\PropertyResource;
use App\Models\PropertyImage;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProperty extends EditRecord
{
    protected static string $resource = PropertyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    public function mount(int | string $record): void
    {
        parent::mount($record);

        // Cargar relaciones necesarias
        $this->record->load('images');
    }

    public function deletePropertyImage(int $id): void
    {
        $image = PropertyImage::find($id);
        if ($image && $image->property_id === $this->record->id) {
            // Si es la portada y hay más imágenes, marcar la primera como portada
            if ($image->is_portada) {
                $otherImage = $this->record->images()->where('id', '!=', $id)->first();
                if ($otherImage) {
                    $otherImage->update(['is_portada' => true]);
                }
            }
            
            $image->delete();
            \Filament\Notifications\Notification::make()
                ->success()
                ->title('Imagen eliminada')
                ->send();
            
            // Recargar la relación de imágenes
            $this->record->load('images');
        }
    }

    public function setPortada(int $id): void
    {
        $image = PropertyImage::find($id);
        if ($image && $image->property_id === $this->record->id) {
            // Desmarcar todas las imágenes como portada
            $this->record->images()->update(['is_portada' => false]);
            
            // Marcar la seleccionada como portada
            $image->update(['is_portada' => true]);
            
            \Filament\Notifications\Notification::make()
                ->success()
                ->title('Imagen marcada como portada')
                ->send();
            
            // Recargar la relación de imágenes
            $this->record->load('images');
        }
    }
}
