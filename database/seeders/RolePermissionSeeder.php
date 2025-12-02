<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear roles básicos
        $adminRoleId = (string) Str::uuid();
        $userRoleId = (string) Str::uuid();

        DB::table('roles')->insert([
            [
                'id' => $adminRoleId,
                'role' => 'Administrador',
                'identifier' => 'admin',
                'all_permission' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => $userRoleId,
                'role' => 'Usuario',
                'identifier' => 'user',
                'all_permission' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Crear permisos básicos
        $permissions = [
            // Dashboard
            [
                'name' => 'Ver Dashboard',
                'identifier' => 'view-dashboard',
                'route' => 'filament.admin.pages.dashboard',
                'panel_ids' => ['admin'],
            ],
            
            // User Manager (del plugin)
            [
                'name' => 'Gestionar Usuarios',
                'identifier' => 'manage-users',
                'route' => 'filament.admin.user-manager.resources.users.*',
                'panel_ids' => ['admin'],
            ],
            [
                'name' => 'Gestionar Roles',
                'identifier' => 'manage-roles',
                'route' => 'filament.admin.user-manager.resources.roles.*',
                'panel_ids' => ['admin'],
            ],
            [
                'name' => 'Gestionar Permisos',
                'identifier' => 'manage-permissions',
                'route' => 'filament.admin.user-manager.resources.permissions.*',
                'panel_ids' => ['admin'],
            ],
            
            // Oficinas
            [
                'name' => 'Ver Oficinas',
                'identifier' => 'view-offices',
                'route' => 'filament.admin.resources.offices.index',
                'panel_ids' => ['admin'],
            ],
            [
                'name' => 'Crear Oficinas',
                'identifier' => 'create-offices',
                'route' => 'filament.admin.resources.offices.create',
                'panel_ids' => ['admin'],
            ],
            [
                'name' => 'Editar Oficinas',
                'identifier' => 'edit-offices',
                'route' => 'filament.admin.resources.offices.edit',
                'panel_ids' => ['admin'],
            ],
            [
                'name' => 'Eliminar Oficinas',
                'identifier' => 'delete-offices',
                'route' => 'filament.admin.resources.offices.delete',
                'panel_ids' => ['admin'],
            ],
            
            // Inquilinos
            [
                'name' => 'Ver Inquilinos',
                'identifier' => 'view-tenants',
                'route' => 'filament.admin.resources.tenants.index',
                'panel_ids' => ['admin'],
            ],
            [
                'name' => 'Crear Inquilinos',
                'identifier' => 'create-tenants',
                'route' => 'filament.admin.resources.tenants.create',
                'panel_ids' => ['admin'],
            ],
            [
                'name' => 'Editar Inquilinos',
                'identifier' => 'edit-tenants',
                'route' => 'filament.admin.resources.tenants.edit',
                'panel_ids' => ['admin'],
            ],
            [
                'name' => 'Ver Detalle Inquilinos',
                'identifier' => 'view-detail-tenants',
                'route' => 'filament.admin.resources.tenants.view',
                'panel_ids' => ['admin'],
            ],
            [
                'name' => 'Eliminar Inquilinos',
                'identifier' => 'delete-tenants',
                'route' => 'filament.admin.resources.tenants.delete',
                'panel_ids' => ['admin'],
            ],
            
            // Propietarios
            [
                'name' => 'Ver Propietarios',
                'identifier' => 'view-owners',
                'route' => 'filament.admin.resources.owners.index',
                'panel_ids' => ['admin'],
            ],
            [
                'name' => 'Crear Propietarios',
                'identifier' => 'create-owners',
                'route' => 'filament.admin.resources.owners.create',
                'panel_ids' => ['admin'],
            ],
            [
                'name' => 'Editar Propietarios',
                'identifier' => 'edit-owners',
                'route' => 'filament.admin.resources.owners.edit',
                'panel_ids' => ['admin'],
            ],
            [
                'name' => 'Ver Detalle Propietarios',
                'identifier' => 'view-detail-owners',
                'route' => 'filament.admin.resources.owners.view',
                'panel_ids' => ['admin'],
            ],
            [
                'name' => 'Eliminar Propietarios',
                'identifier' => 'delete-owners',
                'route' => 'filament.admin.resources.owners.delete',
                'panel_ids' => ['admin'],
            ],
            
            // Rentas
            [
                'name' => 'Ver Rentas',
                'identifier' => 'view-rents',
                'route' => 'filament.admin.resources.rents.index',
                'panel_ids' => ['admin'],
            ],
            [
                'name' => 'Crear Rentas',
                'identifier' => 'create-rents',
                'route' => 'filament.admin.resources.rents.create',
                'panel_ids' => ['admin'],
            ],
            [
                'name' => 'Editar Rentas',
                'identifier' => 'edit-rents',
                'route' => 'filament.admin.resources.rents.edit',
                'panel_ids' => ['admin'],
            ],
            [
                'name' => 'Ver Detalle Rentas',
                'identifier' => 'view-detail-rents',
                'route' => 'filament.admin.resources.rents.view',
                'panel_ids' => ['admin'],
            ],
            [
                'name' => 'Eliminar Rentas',
                'identifier' => 'delete-rents',
                'route' => 'filament.admin.resources.rents.delete',
                'panel_ids' => ['admin'],
            ],
            
            // Solicitudes de Inquilinos
            [
                'name' => 'Ver Solicitudes de Inquilinos',
                'identifier' => 'view-tenant-requests',
                'route' => 'filament.admin.resources.tenant-requests.index',
                'panel_ids' => ['admin'],
            ],
            [
                'name' => 'Crear Solicitudes de Inquilinos',
                'identifier' => 'create-tenant-requests',
                'route' => 'filament.admin.resources.tenant-requests.create',
                'panel_ids' => ['admin'],
            ],
            [
                'name' => 'Editar Solicitudes de Inquilinos',
                'identifier' => 'edit-tenant-requests',
                'route' => 'filament.admin.resources.tenant-requests.edit',
                'panel_ids' => ['admin'],
            ],
            [
                'name' => 'Eliminar Solicitudes de Inquilinos',
                'identifier' => 'delete-tenant-requests',
                'route' => 'filament.admin.resources.tenant-requests.delete',
                'panel_ids' => ['admin'],
            ],
            
            // Solicitudes de Propietarios
            [
                'name' => 'Ver Solicitudes de Propietarios',
                'identifier' => 'view-owner-requests',
                'route' => 'filament.admin.resources.owner-requests.index',
                'panel_ids' => ['admin'],
            ],
            [
                'name' => 'Crear Solicitudes de Propietarios',
                'identifier' => 'create-owner-requests',
                'route' => 'filament.admin.resources.owner-requests.create',
                'panel_ids' => ['admin'],
            ],
            [
                'name' => 'Editar Solicitudes de Propietarios',
                'identifier' => 'edit-owner-requests',
                'route' => 'filament.admin.resources.owner-requests.edit',
                'panel_ids' => ['admin'],
            ],
            [
                'name' => 'Eliminar Solicitudes de Propietarios',
                'identifier' => 'delete-owner-requests',
                'route' => 'filament.admin.resources.owner-requests.delete',
                'panel_ids' => ['admin'],
            ],
        ];

        $permissionIds = [];
        foreach ($permissions as $permission) {
            $permissionId = (string) Str::uuid();
            $permissionIds[] = $permissionId;

            DB::table('permissions')->insert([
                'id' => $permissionId,
                'name' => $permission['name'],
                'identifier' => $permission['identifier'],
                'route' => $permission['route'],
                'panel_ids' => json_encode($permission['panel_ids']),
                'status' => true,
                'parent_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Asignar todos los permisos al rol de administrador
        foreach ($permissionIds as $permissionId) {
            DB::table('role_permissions')->insert([
                'role_id' => $adminRoleId,
                'permission_id' => $permissionId,
            ]);
        }

        $this->command->info('Roles y permisos creados exitosamente!');
        $this->command->info('- Rol Administrador creado con todos los permisos');
        $this->command->info('- Rol Usuario creado (sin permisos asignados)');
        $this->command->info('- ' . count($permissions) . ' permisos creados');
    }
}

