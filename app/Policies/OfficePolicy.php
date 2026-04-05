<?php

namespace App\Policies;

use App\Models\Office;
use App\Models\User;

class OfficePolicy
{
    public function viewAny(User $user): bool { return $user->hasPermissionTo('Ver Oficinas'); }
    public function view(User $user, Office $office): bool { return $user->hasPermissionTo('Ver Oficinas'); }
    public function create(User $user): bool { return $user->hasPermissionTo('Crear Oficinas'); }
    public function update(User $user, Office $office): bool { return $user->hasPermissionTo('Editar Oficinas'); }
    public function delete(User $user, Office $office): bool { return $user->hasPermissionTo('Eliminar Oficinas'); }
}