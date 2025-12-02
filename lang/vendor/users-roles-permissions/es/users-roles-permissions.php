<?php

/*
 * Copyright CWSPS154. All rights reserved.
 * @auth CWSPS154
 * @link  https://github.com/CWSPS154
 */

return [
    'system' => 'Sistema',
    'user' => [
        'manager' => 'Gestor de Usuarios',
        'resource' => [
            'user' => 'Usuario',
            'form' => [
                'name' => 'Nombre',
                'email' => 'Correo Electrónico',
                'mobile' => 'Teléfono Móvil',
                'role' => 'Rol',
                'password' => 'Contraseña',
                'confirm-password' => 'Confirmar Contraseña',
                'active' => 'Activo',
                'profile-image' => 'Imagen de Perfil',
            ],
            'table' => [
                'image' => 'Imagen',
                'online' => 'En Línea',
                'verified' => 'Verificado',
                'created-at' => 'Creado En',
                'updated-at' => 'Actualizado En',
                'deleted-at' => 'Eliminado En',
                'created-by' => 'Creado Por',
                'updated-by' => 'Actualizado Por',
                'deleted-by' => 'Eliminado Por',
                'actions' => [
                    'edit-profile' => 'Editar Perfil',
                ],
            ],
        ],
        'validation' => [
            'have-access-page' => 'No tienes permiso para acceder a esta página.',
            'is-active' => 'Tu cuenta no está activa en este momento.',
        ],
    ],
    'role' => [
        'resource' => [
            'role' => 'Rol',
            'form' => [
                'name' => 'Rol',
                'identifier' => 'Identificador',
                'all-permission' => 'Todos los Permisos',
                'is-active' => 'Está Activo',
                'permissions' => 'Permisos',
            ],
            'table' => [
                'created-at' => 'Creado En',
                'updated-at' => 'Actualizado En',
            ],
        ],
    ],
    'permission' => [
        'resource' => [
            'permission' => 'Permiso',
            'form' => [
                'name' => 'Nombre',
                'identifier' => 'Identificador',
                'panel-ids' => 'Panel',
                'children' => 'Hijos',
                'route' => 'Ruta',
                'status' => 'Estado',
            ],
            'table' => [
                'created-at' => 'Creado En',
                'updated-at' => 'Actualizado En',
            ],
        ],
        'validation' => [
            'unique-route' => 'No existe :attribute con este :value',
            'no-panel-id' => 'No se encontró panel con id: :panel_id',
        ],
        'console' => [
            'sync-permissions-config-not-found' => 'No se encontraron archivos de configuración :config.',
            'sync-permissions-config-loading' => 'Cargando permisos desde: :path',
            'sync-permissions-empty' => 'No se encontraron permisos en los archivos de configuración.',
            'sync-permissions-completed' => 'Permisos sincronizados exitosamente.',
            'sync-permissions' => 'Sincronizando permiso: :identifier',
            'sync-permission-deleted-permissions' => 'Permisos eliminados: :identifiers',
            'sync-permission-invalid-data-format' => 'Formato de permiso inválido. Permiso: :permission',
        ],
        'import' => [
            'completed' => 'La importación de permisos se completó y :successful_rows :row importado.',
            'failed' => ' :failedRowsCount :row falló al importar.',
            'helper-text' => [
                'identifier' => 'Esto se usará para identificar el permiso en la base de datos. Debe ser único.',
                'panel-ids' => 'Debe haber al menos un identificador de panel que coincida con este valor, múltiples valores deben estar separados por comas',
                'route' => 'Debe haber al menos un nombre de ruta que coincida con este valor o valor nulo',
                'parent' => 'El identificador del permiso padre',
            ],
        ],
        'export' => [
            'completed' => 'La exportación de permisos se completó y :successful_rows :row exportado.',
            'failed' => ' :failedRowsCount :row falló al exportar.',
        ],
    ],
];

