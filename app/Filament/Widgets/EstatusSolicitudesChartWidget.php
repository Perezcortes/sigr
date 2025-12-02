<?php

namespace App\Filament\Widgets;

use App\Models\TenantRequest;
use App\Models\OwnerRequest;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class EstatusSolicitudesChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Distribución de Solicitudes por Estatus';
    
    protected static ?int $sort = 5;

    protected function getData(): array
    {
        // Combinar solicitudes de inquilinos y propietarios
        $tenantEstatus = TenantRequest::select('estatus', DB::raw('count(*) as total'))
            ->groupBy('estatus')
            ->pluck('total', 'estatus')
            ->toArray();

        $ownerEstatus = OwnerRequest::select('estatus', DB::raw('count(*) as total'))
            ->groupBy('estatus')
            ->pluck('total', 'estatus')
            ->toArray();

        // Combinar ambos
        $allEstatus = ['nueva' => 0, 'en_proceso' => 0, 'completada' => 0, 'rechazada' => 0];
        
        foreach ($tenantEstatus as $estatus => $count) {
            $allEstatus[$estatus] = ($allEstatus[$estatus] ?? 0) + $count;
        }
        
        foreach ($ownerEstatus as $estatus => $count) {
            $allEstatus[$estatus] = ($allEstatus[$estatus] ?? 0) + $count;
        }

        // Filtrar solo los que tienen datos
        $labels = [];
        $data = [];
        
        foreach ($allEstatus as $estatus => $count) {
            if ($count > 0) {
                $labels[] = ucfirst(str_replace('_', ' ', $estatus));
                $data[] = $count;
            }
        }

        // Colores personalizados
        $colors = [
            'Nueva' => '#26cad3',      // Cian
            'En proceso' => '#fe5f3b', // Naranja
            'Completada' => '#10b981', // Verde
            'Rechazada' => '#ef4444',  // Rojo
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
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'pie';
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

