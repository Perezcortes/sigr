#!/bin/bash

# Script de deploy para limpiar caché y actualizar aplicación

echo "🚀 Iniciando deploy..."

# Limpiar todos los cachés
echo "📦 Limpiando cachés..."
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Regenerar cachés de producción
echo "⚡ Regenerando cachés de producción..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Actualizar assets de Filament
echo "🎨 Actualizando assets de Filament..."
php artisan filament:assets --force

# Ejecutar migraciones si hay nuevas
echo "🗄️  Ejecutando migraciones..."
php artisan migrate --force

echo "✅ Deploy completado!"

