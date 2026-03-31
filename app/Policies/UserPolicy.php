<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool { return $user->hasPermissionTo('Gestionar Usuarios'); }
    public function view(User $user, User $model): bool { return $user->hasPermissionTo('Gestionar Usuarios'); }
    public function create(User $user): bool { return $user->hasPermissionTo('Gestionar Usuarios'); }
    public function update(User $user, User $model): bool { return $user->hasPermissionTo('Gestionar Usuarios'); }
    public function delete(User $user, User $model): bool { return $user->hasPermissionTo('Gestionar Usuarios'); }
}