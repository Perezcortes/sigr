# API de Autenticación - Admrentas

Este documento describe las APIs de autenticación disponibles en el proyecto admrentas para ser consumidas por la aplicación móvil apprentas.

## Base URL

```
http://localhost:8000/api
```

*Nota: Cambiar en producción a la URL del servidor*

## Autenticación

Todas las rutas protegidas requieren un token de autenticación en el header:

```
Authorization: Bearer {token}
```

## Endpoints Disponibles

### 1. Registro de Usuario

**Endpoint:** `POST /api/register`

**Request Body:**
```json
{
    "name": "Juan Pérez",
    "email": "juan@ejemplo.com",
    "password": "mi_contraseña_segura",
    "password_confirmation": "mi_contraseña_segura"
}
```

**Response Success (201):**
```json
{
    "success": true,
    "message": "Usuario registrado exitosamente",
    "data": {
        "user": {
            "id": 1,
            "name": "Juan Pérez",
            "email": "juan@ejemplo.com"
        },
        "token": "1|abc123def456..."
    }
}
```

**Response Error (422):**
```json
{
    "success": false,
    "message": "Error de validación",
    "errors": {
        "email": ["El campo email ya está en uso"]
    }
}
```

---

### 2. Login de Usuario

**Endpoint:** `POST /api/login`

**Request Body:**
```json
{
    "email": "juan@ejemplo.com",
    "password": "mi_contraseña_segura"
}
```

**Response Success (200):**
```json
{
    "success": true,
    "message": "Login exitoso",
    "data": {
        "user": {
            "id": 1,
            "name": "Juan Pérez",
            "email": "juan@ejemplo.com",
            "mobile": "+52 1234567890"
        },
        "token": "2|xyz789abc123..."
    }
}
```

**Response Error (401):**
```json
{
    "success": false,
    "message": "Credenciales inválidas",
    "errors": {
        "login": ["Las credenciales proporcionadas son incorrectas"]
    }
}
```

**Response Error - Usuario Inactivo (403):**
```json
{
    "success": false,
    "message": "Usuario inactivo",
    "errors": {
        "login": ["Tu cuenta está desactivada. Contacta al administrador"]
    }
}
```

---

### 3. Cerrar Sesión (Protegido)

**Endpoint:** `POST /api/logout`

**Headers:**
```
Authorization: Bearer {token}
```

**Response Success (200):**
```json
{
    "success": true,
    "message": "Sesión cerrada exitosamente"
}
```

---

### 4. Obtener Usuario Autenticado (Protegido)

**Endpoint:** `GET /api/user`

**Headers:**
```
Authorization: Bearer {token}
```

**Response Success (200):**
```json
{
    "success": true,
    "data": {
        "user": {
            "id": 1,
            "name": "Juan Pérez",
            "email": "juan@ejemplo.com",
            "mobile": "+52 1234567890"
        }
    }
}
```

---

## Validaciones

### Registro
- `name`: Requerido, string, máximo 255 caracteres
- `email`: Requerido, email válido, único en la base de datos
- `password`: Requerido, mínimo 6 caracteres, debe coincidir con `password_confirmation`

### Login
- `email`: Requerido, formato de email válido
- `password`: Requerido

---

## Códigos de Estado HTTP

- `200` - Éxito
- `201` - Creado exitosamente
- `401` - No autorizado (credenciales inválidas)
- `403` - Prohibido (usuario inactivo)
- `422` - Error de validación
- `500` - Error interno del servidor

---

## Notas de Implementación

1. Los tokens se generan usando Laravel Sanctum
2. Los tokens no expiran por defecto, pero pueden configurarse
3. El campo `is_active` debe estar en `true` para permitir login
4. El campo `last_seen` se actualiza automáticamente en cada login
5. Todas las contraseñas se hashean con bcrypt antes de almacenarse

---

## Ejemplo de Uso con cURL

### Registro
```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Juan Pérez",
    "email": "juan@ejemplo.com",
    "password": "mi_contraseña",
    "password_confirmation": "mi_contraseña"
  }'
```

### Login
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "juan@ejemplo.com",
    "password": "mi_contraseña"
  }'
```

### Obtener Usuario
```bash
curl -X GET http://localhost:8000/api/user \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

---

## Integración con Apprentas

El proyecto apprentas ya está configurado para consumir estas APIs mediante:

- **ApiService** (`app/Services/ApiService.php`): Maneja las peticiones HTTP
- **LoginController** (`app/Http/Controllers/Auth/LoginController.php`): Procesa el login
- **RegisterController** (`app/Http/Controllers/Auth/RegisterController.php`): Procesa el registro

La URL base se configura en el archivo `.env` de apprentas:
```
API_BASE_URL=http://localhost:8000/api
```

