<?php

namespace App\Policies;

use App\Models\Owner;
use App\Models\User;

class OwnerPolicy
{
    public function viewAny(User $user): bool { return $user->hasPermissionTo('Ver Propietarios'); }
    public function view(User $user, Owner $owner): bool { return $user->hasPermissionTo('Ver Detalle Propietarios'); }
    public function create(User $user): bool { return $user->hasPermissionTo('Crear Propietarios'); }
    public function update(User $user, Owner $owner): bool { return $user->hasPermissionTo('Editar Propietarios'); }
    public function delete(User $user, Owner $owner): bool { return $user->hasPermissionTo('Eliminar Propietarios'); }
}