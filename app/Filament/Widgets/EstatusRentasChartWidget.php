<?php

namespace App\Filament\Widgets;

use App\Models\Rent;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class EstatusRentasChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Distribución de Rentas por Estatus';
    
    protected static ?int $sort = 4;

    protected function getData(): array
    {
        $estatus = Rent::select('estatus', DB::raw('count(*) as total'))
            ->groupBy('estatus')
            ->pluck('total', 'estatus')
            ->toArray();

        $labels = array_keys($estatus);
        $data = array_values($estatus);

        // Colores personalizados
        $colors = [
            'nueva' => '#26cad3',      // Cian
            'en_proceso' => '#fe5f3b', // Naranja
            'activa' => '#10b981',     // Verde
            'completada' => '#3b82f6', // Azul
            'cancelada' => '#ef4444',  // Rojo
        ];

        $backgroundColors = [];
        $borderColors = [];
        
        foreach ($labels as $label) {
            $color = $colors[$label] ?? '#6b7280';
            $backgroundColors[] = $color . '80'; // 50% transparencia
            $borderColors[] = $color;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Cantidad',
                    'data' => $data,
                    'backgroundColor' => $backgroundColors,
                    'borderColor' => $borderColors,
                    'borderWidth' => 2,
                ],
            ],
            'labels' => array_map('ucfirst', $labels),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'right',
                ],
            ],
        ];
    }
}

