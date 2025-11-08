#!/bin/bash
# Script para arreglar assets en producción

echo "1. Publicando assets de Filament..."
php artisan filament:assets

echo ""
echo "2. Publicando assets de Laravel..."
php artisan vendor:publish --tag=laravel-assets --force

echo ""
echo "3. Verificando que los archivos existen..."
if [ -f "public/css/filament/filament/app.css" ]; then
    echo "✓ public/css/filament/filament/app.css existe"
else
    echo "✗ public/css/filament/filament/app.css NO existe"
fi

if [ -f "public/js/filament/filament/app.js" ]; then
    echo "✓ public/js/filament/filament/app.js existe"
else
    echo "✗ public/js/filament/filament/app.js NO existe"
fi

echo ""
echo "4. Ajustando permisos..."
chmod -R 755 public/css public/js 2>/dev/null || echo "No se pudieron ajustar permisos (puede ser normal)"

echo ""
echo "5. Limpiando cache..."
php artisan optimize:clear

echo ""
echo "6. Optimizando para producción..."
php artisan config:cache
php artisan view:cache
php artisan route:cache

echo ""
echo "✓ Proceso completado!"
echo ""
echo "Ahora verifica en el navegador (F12 > Network) si los assets se cargan correctamente."


