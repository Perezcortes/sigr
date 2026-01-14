<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpiar la caché de permisos de Spatie para evitar errores
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Definir la lista de permisos
        // Spatie solo necesita el 'name'. La lógica de rutas se maneja en Policies o Middleware.
        $permissions = [
            // Dashboard y Sistema
            'Ver Dashboard',
            'Gestionar Usuarios',
            'Gestionar Roles',
            'Gestionar Permisos',

            // Oficinas
            'Ver Oficinas',
            'Crear Oficinas',
            'Editar Oficinas',
            'Eliminar Oficinas',

            // Inquilinos
            'Ver Inquilinos',
            'Crear Inquilinos',
            'Editar Inquilinos',
            'Ver Detalle Inquilinos',
            'Eliminar Inquilinos',

            // Propietarios
            'Ver Propietarios',
            'Crear Propietarios',
            'Editar Propietarios',
            'Ver Detalle Propietarios',
            'Eliminar Propietarios',

            // Rentas
            'Ver Rentas',
            'Crear Rentas',
            'Editar Rentas',
            'Ver Detalle Rentas',
            'Eliminar Rentas',

            // Solicitudes Inquilinos
            'Ver Solicitudes de Inquilinos',
            'Crear Solicitudes de Inquilinos',
            'Editar Solicitudes de Inquilinos',
            'Eliminar Solicitudes de Inquilinos',

            // Solicitudes Propietarios
            'Ver Solicitudes de Propietarios',
            'Crear Solicitudes de Propietarios',
            'Editar Solicitudes de Propietarios',
            'Eliminar Solicitudes de Propietarios',
        ];

        // Crear los permisos en la base de datos
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Crear Roles
        $roleAdmin = Role::firstOrCreate(['name' => 'Administrador']);
        $roleGerente = Role::firstOrCreate(['name' => 'Gerente']);
        $roleAsesor = Role::firstOrCreate(['name' => 'Asesor']);
        $roleCliente = Role::firstOrCreate(['name' => 'Cliente']);

        // Asignar permisos a los roles
        // Al Admin le damos todos los permisos existentes
        $roleAdmin->givePermissionTo(Permission::all());

        // GERENTE:
        // Puede ver todo de oficinas, crear oficinas y ver usuarios (asesores)
        $roleGerente->givePermissionTo([
            'Ver Dashboard',
            'Gestionar Usuarios', // Para ver a los asesores
            'Ver Oficinas',
            'Crear Oficinas',
            'Editar Oficinas',
            'Ver Inquilinos', 
            // Aquí los demás permisos que requiera el Gerente
        ]);

        // ASESOR:
        $roleAsesor->givePermissionTo([
            'Ver Dashboard',
            'Ver Oficinas', // Necesario para entrar al módulo
            'Ver Inquilinos',
            'Crear Inquilinos',
            'Editar Inquilinos',
            // Permisos limitados a su operación
        ]);

        // Al Cliente le podrías dar permisos específicos, por ejemplo: 
        $roleCliente->givePermissionTo(['Ver Rentas']);

        // CREAR EL USUARIO ADMINISTRADOR POR DEFECTO
        $user = User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Admin Pro',
                'password' => Hash::make('12345'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        // Asignar el rol de Administrador al usuario
        $user->assignRole($roleAdmin);

        $this->command->info('Roles y permisos de Spatie creados exitosamente.');
        $this->command->info('Usuario Admin creado: admin@admin.com / 12345');
    }
}