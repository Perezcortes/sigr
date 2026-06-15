<?php

namespace App\Policies;

use App\Models\OwnerRequest;
use App\Models\User;

class OwnerRequestPolicy
{
    public function viewAny(User $user): bool { return $user->hasPermissionTo('Ver Solicitudes de Propietarios'); }
    public function view(User $user, OwnerRequest $ownerRequest): bool { return $user->hasPermissionTo('Ver Solicitudes de Propietarios'); }
    public function create(User $user): bool { return $user->hasPermissionTo('Crear Solicitudes de Propietarios'); }
    public function update(User $user, OwnerRequest $ownerRequest): bool { return $user->hasPermissionTo('Editar Solicitudes de Propietarios'); }
    public function delete(User $user, OwnerRequest $ownerRequest): bool { return $user->hasPermissionTo('Eliminar Solicitudes de Propietarios'); }
}