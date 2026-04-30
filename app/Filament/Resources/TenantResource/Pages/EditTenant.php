<?php

namespace App\Filament\Resources\TenantResource\Pages;

use App\Filament\Resources\TenantResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTenant extends EditRecord
{
    protected static string $resource = TenantResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // No es columna de tenants: debe ir en el array de fill para que el TextInput muestre el valor.
        $data['login_email'] = $this->record->user?->email
            ?? $this->record->email
            ?? '';

        if ($this->record->user_id) {
            $this->record->loadMissing('user');
            $user = $this->record->user;
            if ($user) {
                $data['portal_is_tenant'] = (bool) $user->is_tenant;
                $data['portal_is_owner'] = (bool) $user->is_owner;
                $data['portal_is_seller'] = (bool) $user->is_seller;
                $data['portal_is_buyer'] = (bool) $user->is_buyer;
            }
        } else {
            $data['portal_is_tenant'] = true;
            $data['portal_is_owner'] = false;
            $data['portal_is_seller'] = false;
            $data['portal_is_buyer'] = false;
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->record->user_id) {
            $this->record->loadMissing('user');
            $user = $this->record->user;
            if ($user) {
                $user->update([
                    'is_tenant' => (bool) ($data['portal_is_tenant'] ?? false),
                    'is_owner' => (bool) ($data['portal_is_owner'] ?? false),
                    'is_seller' => (bool) ($data['portal_is_seller'] ?? false),
                    'is_buyer' => (bool) ($data['portal_is_buyer'] ?? false),
                ]);
            }
        }

        unset(
            $data['login_email'],
            $data['portal_is_tenant'],
            $data['portal_is_owner'],
            $data['portal_is_seller'],
            $data['portal_is_buyer'],
        );

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
