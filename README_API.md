# Admrentas - Backend API

## Inicio Rápido

Este proyecto Laravel sirve como backend API para la aplicación móvil **apprentas**.

### Iniciar el Servidor

```bash
php artisan serve
```

El servidor se iniciará en: `http://localhost:8000`

### Rutas de API

Todas las rutas de API están bajo el prefijo `/api`:

- `POST /api/register` - Registro de nuevos usuarios
- `POST /api/login` - Autenticación de usuarios
- `POST /api/logout` - Cerrar sesión (requiere autenticación)
- `GET /api/user` - Obtener datos del usuario autenticado

### Documentación Completa

Ver [API_DOCUMENTATION.md](./API_DOCUMENTATION.md) para detalles completos de cada endpoint.

### Configuración

El proyecto ya está configurado con:
- ✅ Laravel Sanctum para autenticación con tokens
- ✅ CORS habilitado para peticiones desde otras aplicaciones
- ✅ Migraciones ejecutadas (tabla `personal_access_tokens`)
- ✅ Modelo User con soporte para tokens API

### Notas

- El servidor debe estar corriendo en `http://localhost:8000` para que la app móvil pueda consumir la API
- La configuración de CORS de Laravel permite peticiones desde cualquier origen en desarrollo

