<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected ?string $pendingPrimaryRole = null;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->record->loadMissing('roles');
        $data['primary_role'] = $this->record->roles->first()?->name;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->pendingPrimaryRole = isset($data['primary_role']) ? (string) $data['primary_role'] : null;
        unset($data['primary_role']);

        if ($this->pendingPrimaryRole !== 'Cliente') {
            $data['asesor_id'] = null;
            $data['is_owner'] = false;
            $data['is_tenant'] = false;
            $data['is_seller'] = false;
            $data['is_buyer'] = false;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->pendingPrimaryRole !== null && $this->pendingPrimaryRole !== '') {
            $this->record->syncRoles([$this->pendingPrimaryRole]);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
