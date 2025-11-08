# Solución: Assets sin Estilos en Producción

## Problema
Los estilos no se cargan en producción. Esto puede deberse a:
1. Assets de Vite no compilados
2. Assets de Filament no publicados
3. APP_URL mal configurado
4. Permisos incorrectos

## Solución Completa

### En el servidor de producción (Dokploy), ejecuta:

```bash
# 1. Publicar assets de Filament (CRÍTICO)
php artisan filament:assets

# 2. Publicar assets de Laravel
php artisan vendor:publish --tag=laravel-assets --force

# 3. Instalar dependencias de Node.js (si no están instaladas)
npm install

# 4. Compilar assets de Vite para producción
npm run build

# 5. Verificar que existen los directorios
ls -la public/build/
ls -la public/css/filament/
ls -la public/js/filament/

# 6. Asegurar permisos correctos
chmod -R 755 public/build
chmod -R 755 public/css
chmod -R 755 public/js
chown -R www-data:www-data public/build public/css public/js

# 7. Limpiar y optimizar cache
php artisan optimize:clear
php artisan config:cache
php artisan view:cache
php artisan route:cache
php artisan optimize
```

### Si no tienes Node.js en el contenedor Docker:

**Opción 1: Compilar localmente y subir**
```bash
# En tu máquina local:
npm run build

# Luego sube la carpeta public/build/ al servidor
```

**Opción 2: Agregar Node.js al contenedor Docker**
- En Dokploy, asegúrate de que el contenedor tenga Node.js instalado
- O usa un contenedor multi-stage build que compile los assets

### Verificar que funciona:

Después de compilar, deberías ver:
- `public/build/manifest.json` - archivo de manifiesto de Vite
- `public/build/assets/` - directorio con los archivos CSS y JS compilados

## Configuración de .env en Producción

Asegúrate de que en tu `.env` de producción tengas:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://app.rentas.com  # IMPORTANTE: Debe ser la URL completa de tu dominio
ASSET_URL=  # Déjalo vacío o pon la URL base si usas CDN
```

**CRÍTICO:** `APP_URL` debe ser la URL completa de tu dominio en producción. Si está mal configurado, los assets no se cargarán.

## Verificación en el Navegador

1. **Abre la consola del navegador (F12)**
2. **Ve a la pestaña "Network"**
3. **Recarga la página**
4. **Busca archivos CSS/JS que fallen (aparecerán en rojo)**
5. **Verifica la URL completa de los archivos que fallan**

Si ves errores 404 en archivos como:
- `/css/filament/filament/app.css`
- `/js/filament/filament/app.js`

Significa que los assets no están publicados o no se están sirviendo correctamente.

## Si los assets aún no cargan:

1. **Verificar que los archivos existen:**
   ```bash
   ls -la public/css/filament/filament/app.css
   ls -la public/js/filament/filament/app.js
   ```

2. **Verificar permisos:**
   ```bash
   chmod -R 755 public/
   ```

3. **Verificar APP_URL:**
   ```bash
   php artisan tinker --execute="echo config('app.url');"
   ```
   Debe mostrar la URL completa de tu dominio.

4. **Verificar que Nginx/Apache está sirviendo archivos estáticos:**
   - Los archivos en `public/` deben ser accesibles directamente
   - Verifica que no hay reglas de rewrite bloqueando los assets

5. **Limpiar cache completo:**
   ```bash
   php artisan optimize:clear
   php artisan config:clear
   php artisan view:clear
   php artisan route:clear
   ```

