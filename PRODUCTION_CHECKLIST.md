# Checklist para Producción con MySQL

## 1. Configuración de Base de Datos

### Variables de Entorno (.env en producción)
```env
DB_CONNECTION=mysql
DB_HOST=tu_host_mysql
DB_PORT=3306
DB_DATABASE=nombre_base_datos
DB_USERNAME=usuario_mysql
DB_PASSWORD=contraseña_segura
```

### Configuración de MySQL en Dokploy
- Asegúrate de que el contenedor MySQL tenga suficiente memoria
- Configura timeouts apropiados:
  - `wait_timeout = 600`
  - `interactive_timeout = 600`
  - `max_connections = 200`

## 2. Optimizaciones de Laravel

### Ejecutar en el servidor:
```bash
# Limpiar todo
php artisan optimize:clear

# Crear directorio de vistas faltante
mkdir -p vendor/cwsps154/users-roles-permissions/resources/views

# Optimizar para producción
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

## 3. Configuración de PHP-FPM

En Dokploy, asegúrate de que PHP-FPM tenga:
```
request_terminate_timeout = 300
max_execution_time = 300
max_input_time = 300
memory_limit = 256M
```

## 4. Índices en Base de Datos

Verifica que las tablas tengan índices apropiados:
- `users.email` - debe tener índice único
- `offices.clave` - debe tener índice único
- Todas las foreign keys deben tener índices
- Columnas usadas en `where()` y `orderBy()` deben tener índices

## 5. Eager Loading

Ya está configurado en OfficeResource para evitar N+1 queries.

## 6. Verificaciones

```bash
# Verificar migraciones
php artisan migrate:status

# Verificar conexión a base de datos
php artisan tinker --execute="DB::connection()->getPdo(); echo 'Conexión OK';"

# Verificar logs
tail -f storage/logs/laravel.log
```

## 7. Optimizaciones de MySQL

Ejecutar en MySQL:
```sql
-- Optimizar tablas
OPTIMIZE TABLE users;
OPTIMIZE TABLE offices;
OPTIMIZE TABLE cities;
OPTIMIZE TABLE estates;

-- Verificar índices
SHOW INDEX FROM users;
SHOW INDEX FROM offices;
```

## 8. Monitoreo

- Revisar logs de Laravel: `storage/logs/laravel.log`
- Monitorear queries lentas en MySQL
- Verificar uso de memoria y CPU

