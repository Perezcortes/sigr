<?php

namespace App\Policies;

use App\Models\TenantRequest;
use App\Models\User;

class TenantRequestPolicy
{
    public function viewAny(User $user): bool { return $user->hasPermissionTo('Ver Solicitudes de Inquilinos'); }
    public function view(User $user, TenantRequest $tenantRequest): bool { return $user->hasPermissionTo('Ver Solicitudes de Inquilinos'); }
    public function create(User $user): bool { return $user->hasPermissionTo('Crear Solicitudes de Inquilinos'); }
    public function update(User $user, TenantRequest $tenantRequest): bool { return $user->hasPermissionTo('Editar Solicitudes de Inquilinos'); }
    public function delete(User $user, TenantRequest $tenantRequest): bool { return $user->hasPermissionTo('Eliminar Solicitudes de Inquilinos'); }
}