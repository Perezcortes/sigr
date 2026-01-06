# Solución Avanzada: Error MethodNotAllowedHttpException en admin/login

## Problema
El error `The POST method is not supported for route admin/login` persiste incluso después de limpiar cachés.

## Diagnóstico

### 1. Verificar rutas registradas
```bash
php artisan route:list | grep -i "admin/login"
```

Deberías ver algo como:
```
GET|HEAD  admin/login  ...  Filament\Pages\Auth\Login
POST      admin/login  ...  Filament\Pages\Auth\Login
```

Si solo ves GET|HEAD, el problema es que las rutas POST no se están registrando.

### 2. Verificar caché de rutas
```bash
ls -la bootstrap/cache/routes*.php
```

Si existen estos archivos, elimínalos:
```bash
rm -f bootstrap/cache/routes*.php
php artisan route:clear
```

### 3. Verificar OPcache
Si usas OPcache, puede estar cacheando código antiguo:
```bash
php -r "if (function_exists('opcache_reset')) { opcache_reset(); echo 'OPcache limpiado\n'; }"
```

## Soluciones

### Solución 1: Limpieza Agresiva de Caché

Ejecuta el script de deploy mejorado:
```bash
chmod +x deploy.sh
./deploy.sh
```

### Solución 2: Limpieza Manual Completa

```bash
# Limpiar todos los cachés
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
php artisan event:clear

# Eliminar archivos de caché manualmente
rm -f bootstrap/cache/config.php
rm -f bootstrap/cache/routes-v7.php
rm -f bootstrap/cache/routes.php
rm -rf bootstrap/cache/*.php
rm -rf storage/framework/cache/data/*
rm -rf storage/framework/views/*

# Regenerar cachés
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Limpiar OPcache
php -r "if (function_exists('opcache_reset')) opcache_reset();"
```

### Solución 3: Verificar Plugin UsersRolesPermissions

El plugin `UsersRolesPermissionsPlugin` puede estar interfiriendo. Si el problema persiste, prueba temporalmente comentar el plugin:

```php
// En app/Providers/Filament/AdminPanelProvider.php
->plugins([
    // UsersRolesPermissionsPlugin::make()  // Comentar temporalmente
])
```

Luego limpia cachés y prueba de nuevo.

### Solución 4: Reiniciar Servidor Web

Si usas PHP-FPM o Apache, reinicia el servicio:
```bash
# PHP-FPM
sudo service php8.2-fpm restart  # Ajusta la versión según tu instalación

# Apache
sudo service apache2 restart

# Nginx (solo reinicia PHP-FPM)
sudo service php-fpm restart
```

### Solución 5: Verificar .htaccess

Asegúrate de que tu `.htaccess` no esté bloqueando métodos POST. El archivo debería tener:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^ index.php [L]
```

## Verificación Final

Después de aplicar las soluciones, verifica:

1. **Rutas registradas:**
   ```bash
   php artisan route:list | grep "admin/login"
   ```
   Debe mostrar GET y POST.

2. **Probar login:**
   - Abre `https://tudominio.com/admin/login`
   - Intenta iniciar sesión
   - Revisa los logs si falla: `storage/logs/laravel.log`

3. **Verificar logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

## Si el Problema Persiste

1. Verifica la versión de Filament:
   ```bash
   composer show filament/filament
   ```

2. Verifica la versión de Laravel:
   ```bash
   php artisan --version
   ```

3. Revisa los logs del servidor web (Apache/Nginx)

4. Verifica permisos de archivos:
   ```bash
   chmod -R 775 storage bootstrap/cache
   chown -R www-data:www-data storage bootstrap/cache
   ```



