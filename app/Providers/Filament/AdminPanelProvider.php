<?php

namespace App\Providers\Filament;

use App\Filament\Resources\OfficeResource;
use App\Filament\Resources\OwnerResource;
use App\Filament\Resources\RentResource;
use App\Filament\Resources\TenantResource;
use App\Filament\Resources\TenantRequestResource;
use App\Filament\Resources\OwnerRequestResource;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\SaleResource;
use App\Filament\Resources\LeadResource;
use App\Filament\Widgets\ResumenDashboardWidget;
use App\Filament\Widgets\RentasMensualesChartWidget;
use App\Filament\Widgets\SolicitudesMensualesChartWidget;
use App\Filament\Widgets\EstatusRentasChartWidget;
use App\Filament\Widgets\EstatusSolicitudesChartWidget;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Filament\Resources\ApplicationsResource;
use App\Filament\Resources\PropertyResource;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->profile()

            ->navigationGroups([
                'Dashboard',
                'Admin',  
                'Centro de pagos',
                'Interesados',
                'Rentas',           
            ])

            // CONFIGURACIÓN DE COLORES (Rentas.com)
            ->colors([
                'primary' => Color::hex('#161848'),   // Azul Marino
                'secondary' => Color::hex('#26cad3'), // Cian
                'info' => Color::hex('#26cad3'),      // Cian
                'success' => Color::hex('#26cad3'),   // Cian
                'warning' => Color::hex('#fe5f3b'),   // Naranja
                'danger' => Color::hex('#fe5f3b'),    // Naranja
                'gray' => Color::Gray,
            ])

            ->viteTheme('resources/css/filament/admin/theme.css')
            // CONFIGURACIÓN DE BRANDING (Logos)
            ->brandName('SIGR')
            
            // LOGO PARA MODO CLARO (Fondo Blanco)
            ->brandLogo(fn () => request()->routeIs('filament.admin.auth.login')
                ? asset('images/logo-rentas-w.png') 
                : asset('images/logo-rentas-b.png')    
            )

            // LOGO PARA MODO OSCURO (Fondo Negro)
            ->darkModeBrandLogo(fn () => request()->routeIs('filament.admin.auth.login')
                ? asset('images/logo-rentas-b.png') 
                : asset('images/logo-rentas-b.png')         
            )
            
            // ALTURA DEL LOGO (Importante para que en el login se vea grande)
            ->brandLogoHeight(fn () => request()->routeIs('filament.admin.auth.login')
                ? '5rem' // Altura grande en el Login
                : '2rem' // Altura pequeña en el Dashboard
            )
            
            ->brandLogoHeight('2rem')
            ->favicon(asset('images/favicon.ico'))
            
            // ACTIVAR MODO OSCURO 
            ->darkMode(true) 
            
            ->resources([
                OfficeResource::class,
                TenantResource::class,
                OwnerResource::class,
                RentResource::class,
                TenantRequestResource::class,
                OwnerRequestResource::class,
                ApplicationsResource::class,
                PropertyResource::class,
                UserResource::class,
                SaleResource::class,
                LeadResource::class,
            ])
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                //Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                ResumenDashboardWidget::class,
                RentasMensualesChartWidget::class,
                SolicitudesMensualesChartWidget::class,
                EstatusRentasChartWidget::class,
                EstatusSolicitudesChartWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}