<?php

namespace App\Filament\Resources\PropertyResource\Pages;

use App\Filament\Resources\PropertyResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProperty extends CreateRecord
{
    protected static string $resource = PropertyResource::class;

    public function mount(): void
    {
        $userId = request()->query('user_id');

        if (blank($userId)) {
            $this->redirect(PropertyResource::getUrl('index'));
            return;
        }

        parent::mount();

        // Si viene un user_id en la query string, pre-rellenar el formulario
        $this->form->fill([
            'user_id' => (int) $userId,
        ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $userId = request()->query('user_id');

        if ($userId) {
            $data['user_id'] = (int) $userId;
        }

        return $data;
    }
}
