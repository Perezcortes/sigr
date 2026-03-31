<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use App\Models\Service;
use App\Policies\ServicePolicy;
use App\Models\Ticket;
use App\Policies\TicketPolicy;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Gate;
use App\Filament\Resources\AdministrationResource\RelationManagers\ServicesRelationManager;
use App\Filament\Resources\AdministrationResource\RelationManagers\TicketsRelationManager;

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
        // URLs en HTTPS: evita assets/Livewire en http cuando APP_URL sigue en http (staging, env mal copiado).
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        // Dokploy/Traefik: aunque APP_URL sea http://n1.rentas.com, el proxy termina en HTTPS.
        $this->app->booted(function () {
            if ($this->app->runningInConsole()) {
                return;
            }

            $request = $this->app->make('request');
            $forwardedProto = $request->header('X-Forwarded-Proto');

            if (is_string($forwardedProto) && str_contains($forwardedProto, 'https')) {
                URL::forceScheme('https');
            }
        });

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

        Gate::policy(Service::class, ServicePolicy::class);
        Gate::policy(Ticket::class, TicketPolicy::class);
    }
}
