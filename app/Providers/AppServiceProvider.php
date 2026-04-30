<?php

namespace App\Providers;

use App\Filament\Resources\AdministrationResource\RelationManagers\ServicesRelationManager;
use App\Filament\Resources\AdministrationResource\RelationManagers\TicketsRelationManager;
use App\Models\Service;
use App\Models\Ticket;
use App\Policies\RolePolicy;
use App\Policies\ServicePolicy;
use App\Policies\TicketPolicy;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        FilamentColor::register([
            'primary' => Color::hex('#161848'),   // Azul Marino
            'secondary' => Color::hex('#26cad3'), // Cian
            'info' => Color::hex('#26cad3'),      // Cian
            'success' => Color::hex('#26cad3'),   // Cian
            'warning' => Color::hex('#fe5f3b'),   // Naranja
            'danger' => Color::hex('#fe5f3b'),    // Naranja
            'gray' => Color::Slate,               // Gris base para el modo oscuro
        ]);

        // El Administrador se salta todas las reglas
        Gate::before(function ($user, $ability) {
            return $user->hasRole('Administrador') ? true : null;
        });

        // Configuración de Livewire
        Livewire::setScriptRoute(function ($handle) {
            return Route::get('/vendor/livewire/livewire.js', $handle);
        });

        Livewire::component(
            'app.filament.resources.administration-resource.relation-managers.services-relation-manager',
            ServicesRelationManager::class
        );

        Livewire::component(
            'app.filament.resources.administration-resource.relation-managers.tickets-relation-manager',
            TicketsRelationManager::class
        );

        // Registro de Políticas manuales
        Gate::policy(Service::class, ServicePolicy::class);
        Gate::policy(Ticket::class, TicketPolicy::class);

        // Conexión de la Política de Roles de Spatie
        Gate::policy(Role::class, RolePolicy::class);
    }
}
