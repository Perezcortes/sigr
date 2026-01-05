<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

class DiagnoseLoginRoutes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'diagnose:login-routes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnostica las rutas de login de Filament';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Diagnóstico de rutas de login de Filament');
        $this->newLine();

        // Verificar rutas de login
        $loginRoutes = collect(Route::getRoutes())->filter(function ($route) {
            return str_contains($route->uri(), 'admin/login');
        });

        if ($loginRoutes->isEmpty()) {
            $this->error('❌ No se encontraron rutas admin/login');
            $this->warn('Esto indica que Filament no está registrando las rutas correctamente.');
            return 1;
        }

        $this->info('✅ Rutas encontradas:');
        $this->newLine();

        $tableData = [];
        foreach ($loginRoutes as $route) {
            $methods = implode('|', $route->methods());
            $tableData[] = [
                'Métodos' => $methods,
                'URI' => $route->uri(),
                'Nombre' => $route->getName() ?? 'N/A',
                'Acción' => $route->getActionName(),
            ];
        }

        $this->table(['Métodos', 'URI', 'Nombre', 'Acción'], $tableData);

        // Verificar si POST está presente
        $hasPost = $loginRoutes->contains(function ($route) {
            return in_array('POST', $route->methods());
        });

        if (!$hasPost) {
            $this->newLine();
            $this->error('❌ PROBLEMA DETECTADO: No se encontró ruta POST para admin/login');
            $this->warn('Solución: Ejecuta los siguientes comandos:');
            $this->line('  php artisan route:clear');
            $this->line('  php artisan optimize:clear');
            $this->line('  rm -f bootstrap/cache/routes*.php');
            $this->line('  php artisan route:cache');
            return 1;
        }

        $this->newLine();
        $this->info('✅ Las rutas de login están correctamente registradas (GET y POST)');

        // Verificar archivos de caché
        $this->newLine();
        $this->info('📁 Verificando archivos de caché:');
        
        $cacheFiles = [
            'bootstrap/cache/config.php',
            'bootstrap/cache/routes-v7.php',
            'bootstrap/cache/routes.php',
        ];

        foreach ($cacheFiles as $file) {
            if (file_exists(base_path($file))) {
                $this->line("  ✓ {$file} existe");
            } else {
                $this->line("  - {$file} no existe");
            }
        }

        // Verificar OPcache
        $this->newLine();
        if (function_exists('opcache_get_status')) {
            $status = opcache_get_status();
            if ($status && $status['opcache_enabled']) {
                $this->warn('⚠️  OPcache está habilitado. Considera reiniciarlo:');
                $this->line('  php -r "opcache_reset();"');
            } else {
                $this->info('✓ OPcache no está habilitado o no está disponible');
            }
        }

        return 0;
    }
}
