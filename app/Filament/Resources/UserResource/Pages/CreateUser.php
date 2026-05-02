<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected ?string $pendingPrimaryRole = null;

    protected function mutateFormDataBeforeCreate(array $data): array
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

    protected function afterCreate(): void
    {
        if ($this->pendingPrimaryRole !== null && $this->pendingPrimaryRole !== '') {
            $this->record->syncRoles([$this->pendingPrimaryRole]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
