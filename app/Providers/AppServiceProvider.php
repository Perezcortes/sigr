<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use App\Models\Service;
use App\Policies\ServicePolicy;
use App\Models\Ticket;
use App\Policies\TicketPolicy;
use Illuminate\Support\Facades\Route;
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
