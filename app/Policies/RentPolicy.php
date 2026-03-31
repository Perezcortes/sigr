<?php

namespace App\Policies;

use App\Models\Rent;
use App\Models\User;

class RentPolicy
{
    public function viewAny(User $user): bool { return $user->hasPermissionTo('Ver Rentas'); }
    public function view(User $user, Rent $rent): bool { return $user->hasPermissionTo('Ver Detalle Rentas'); }
    public function create(User $user): bool { return $user->hasPermissionTo('Crear Rentas'); }
    public function update(User $user, Rent $rent): bool { return $user->hasPermissionTo('Editar Rentas'); }
    public function delete(User $user, Rent $rent): bool { return $user->hasPermissionTo('Eliminar Rentas'); }
}