<?php

namespace App\Filament\Widgets;

use App\Models\Rent;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class RentasMensualesChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Rentas por Mes';
    
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        // Obtener datos de los últimos 6 meses
        $months = [];
        $data = [];
        
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months[] = $date->format('M Y');
            
            $count = Rent::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
            
            $data[] = $count;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Rentas creadas',
                    'data' => $data,
                    'backgroundColor' => 'rgba(22, 24, 72, 0.1)', // #161848 con transparencia
                    'borderColor' => '#161848',
                    'borderWidth' => 2,
                    'fill' => true,
                ],
            ],
            'labels' => $months,
        ];
    }

    protected function getType(): string
    {
        return 'line';
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
                ],
            ],
        ];
    }
}

