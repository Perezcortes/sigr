#!/bin/bash

# Script de deploy para limpiar caché y actualizar aplicación

echo "🚀 Iniciando deploy..."

# Limpiar todos los cachés de forma agresiva
echo "📦 Limpiando cachés..."
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
php artisan event:clear

# Eliminar archivos de caché manualmente (por si acaso)
echo "🗑️  Eliminando archivos de caché manualmente..."
rm -f bootstrap/cache/config.php
rm -f bootstrap/cache/routes-v7.php
rm -f bootstrap/cache/routes.php
rm -rf bootstrap/cache/*.php
rm -rf storage/framework/cache/data/*
rm -rf storage/framework/views/*

# Regenerar cachés de producción
echo "⚡ Regenerando cachés de producción..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Verificar rutas de login
echo "🔍 Verificando rutas de login..."
php artisan route:list | grep -i "admin/login" || echo "⚠️  No se encontraron rutas admin/login"

# Limpiar assets antiguos de Filament
echo "🗑️  Limpiando assets antiguos de Filament..."
rm -rf public/css/filament
rm -rf public/js/filament

# Actualizar assets de Filament
echo "🎨 Publicando assets de Filament..."
php artisan filament:assets --force

# Verificar que los assets se publicaron correctamente
if [ ! -f "public/js/filament/filament/app.js" ]; then
    echo "⚠️  ADVERTENCIA: Los assets de Filament no se publicaron correctamente"
else
    echo "✅ Assets de Filament publicados correctamente"
fi

# Ejecutar migraciones si hay nuevas
echo "🗄️  Ejecutando migraciones..."
php artisan migrate --force

# Limpiar opcache si está disponible
if command -v php &> /dev/null; then
    echo "🔄 Limpiando OPcache..."
    php -r "if (function_exists('opcache_reset')) { opcache_reset(); echo 'OPcache limpiado\n'; } else { echo 'OPcache no disponible\n'; }"
fi

echo "✅ Deploy completado!"

