# Solución: Assets sin Estilos en Producción

## Problema
Los estilos no se cargan en producción porque los assets de Vite no están compilados.

## Solución

### En el servidor de producción (Dokploy), ejecuta:

```bash
# 1. Instalar dependencias de Node.js (si no están instaladas)
npm install

# 2. Compilar assets para producción
npm run build

# 3. Verificar que se creó el directorio build
ls -la public/build/

# 4. Asegurar permisos correctos
chmod -R 755 public/build
chown -R www-data:www-data public/build
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
ASSET_URL=  # Déjalo vacío o pon la URL base si usas CDN
```

## Si los assets aún no cargan:

1. **Verificar que public/build existe y tiene archivos**
2. **Verificar permisos del directorio public/**
3. **Limpiar cache de Laravel:**
   ```bash
   php artisan optimize:clear
   php artisan view:clear
   ```
4. **Verificar en el navegador (F12) qué archivos están fallando al cargar**

