<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;

class ServicePolicy
{
    /**
     * Determina si el usuario puede ver la lista.
     */
    public function viewAny(User $user): bool
    {
        return true; 
    }

    /**
     * Determina si el usuario puede crear registros.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determina si el usuario puede editar.
     */
    public function update(User $user, Service $service): bool
    {
        return true; 
    }

    /**
     * Determina si el usuario puede borrar.
     */
    public function delete(User $user, Service $service): bool
    {
        return true; 
    }
}