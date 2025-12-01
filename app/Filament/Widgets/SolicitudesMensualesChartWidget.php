<?php

namespace App\Filament\Widgets;

use App\Models\TenantRequest;
use App\Models\OwnerRequest;
use Filament\Widgets\ChartWidget;

class SolicitudesMensualesChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Solicitudes por Mes';
    
    protected static ?int $sort = 3;

    protected function getData(): array
    {
        // Obtener datos de los últimos 6 meses
        $months = [];
        $dataInquilinos = [];
        $dataPropietarios = [];
        
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months[] = $date->format('M Y');
            
            $inquilinos = TenantRequest::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
            
            $propietarios = OwnerRequest::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
            
            $dataInquilinos[] = $inquilinos;
            $dataPropietarios[] = $propietarios;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Inquilinos',
                    'data' => $dataInquilinos,
                    'backgroundColor' => 'rgba(38, 202, 211, 0.2)', // #26cad3 con transparencia
                    'borderColor' => '#26cad3',
                    'borderWidth' => 2,
                ],
                [
                    'label' => 'Propietarios',
                    'data' => $dataPropietarios,
                    'backgroundColor' => 'rgba(254, 95, 59, 0.2)', // #fe5f3b con transparencia
                    'borderColor' => '#fe5f3b',
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $months,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
            ],
        ];
    }
}

