<?php

namespace App\Filament\Widgets;

use App\Models\Rent;
use App\Models\TenantRequest;
use App\Models\OwnerRequest;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ResumenDashboardWidget extends BaseWidget
{
    protected function getStats(): array
    {
        // Rentas
        $rentasActivas = Rent::whereIn('estatus', ['activa', 'activo'])->count();
        $rentasEnProceso = Rent::whereIn('estatus', ['en_proceso', 'proceso'])->count();
        $totalRentas = Rent::count();
        
        // Solicitudes
        $solicitudesNuevas = TenantRequest::where('estatus', 'nueva')->count() 
            + OwnerRequest::where('estatus', 'nueva')->count();
        $solicitudesEnProceso = TenantRequest::where('estatus', 'en_proceso')->count() 
            + OwnerRequest::where('estatus', 'en_proceso')->count();
        $totalSolicitudes = TenantRequest::count() + OwnerRequest::count();

        return [
            Stat::make('Rentas Activas', $rentasActivas)
                ->description('De ' . $totalRentas . ' total')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
            
            Stat::make('Rentas en Proceso', $rentasEnProceso)
                ->description('Pendientes de gestión')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
            
            Stat::make('Solicitudes Nuevas', $solicitudesNuevas)
                ->description('Pendientes de revisión')
                ->descriptionIcon('heroicon-m-bell')
                ->color('info'),
            
            Stat::make('Solicitudes en Proceso', $solicitudesEnProceso)
                ->description('De ' . $totalSolicitudes . ' total')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('warning'),
        ];
    }
}

