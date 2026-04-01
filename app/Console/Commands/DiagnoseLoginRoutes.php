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

        // Filament 3: admin/login solo es GET; el envío va por Livewire (p. ej. POST a /livewire/update).
        $hasPost = $loginRoutes->contains(function ($route) {
            return in_array('POST', $route->methods());
        });

        if (!$hasPost) {
            $this->newLine();
            $this->info('ℹ️  Filament 3: login es GET en admin/login; no hace falta POST ahí (usa Livewire).');
            $this->warn('Si ves 405 al enviar el formulario, suele ser Livewire sin cargar (HTTPS/APP_URL/proxy).');
        } else {
            $this->newLine();
            $this->info('✅ También hay POST registrado en una ruta admin/login (poco habitual en Filament 3).');
        }

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
