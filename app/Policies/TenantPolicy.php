<?php

namespace App\Policies;

use App\Models\Tenant;
use App\Models\User;

class TenantPolicy
{
    public function viewAny(User $user): bool 
    { 
        // Si es Admin/Gerente pasa directo. Si no, checa el permiso específico.
        return $user->hasAnyRole(['Administrador', 'Gerente']) || $user->hasPermissionTo('Ver Inquilinos'); 
    }
    
    public function view(User $user, Tenant $tenant): bool 
    { 
        return $user->hasAnyRole(['Administrador', 'Gerente']) || $user->hasPermissionTo('Ver Detalle Inquilinos'); 
    }

    public function create(User $user): bool { return $user->hasPermissionTo('Crear Inquilinos'); }
    public function update(User $user, Tenant $tenant): bool { return $user->hasPermissionTo('Editar Inquilinos'); }
    public function delete(User $user, Tenant $tenant): bool { return $user->hasPermissionTo('Eliminar Inquilinos'); }
}