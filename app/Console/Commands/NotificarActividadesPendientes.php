<?php

namespace App\Console\Commands;

use App\Models\LeadActivity;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;

class NotificarActividadesPendientes extends Command
{
    protected $signature   = 'leads:notificar-actividades';
    protected $description = 'Envía notificaciones a los agentes sobre actividades programadas para hoy.';

    public function handle(): int
    {
        $actividades = LeadActivity::with(['lead', 'user'])
            ->where('completada', false)
            ->whereDate('fecha', today())
            ->get();

        if ($actividades->isEmpty()) {
            $this->info('Sin actividades para notificar hoy.');
            return self::SUCCESS;
        }

        $porUsuario = $actividades->groupBy('user_id');

        foreach ($porUsuario as $userId => $items) {
            $user = $items->first()->user;

            if (! $user) {
                continue;
            }

            foreach ($items as $actividad) {
                $leadNombre = $actividad->lead?->nombre ?? 'Interesado';
                $hora       = $actividad->hora ? " a las {$actividad->hora}" : '';

                Notification::make()
                    ->title("Actividad programada{$hora}: {$leadNombre}")
                    ->body($actividad->descripcion)
                    ->icon('heroicon-o-calendar-days')
                    ->iconColor('warning')
                    ->actions([
                        \Filament\Notifications\Actions\Action::make('ver')
                            ->label('Ver interesado')
                            ->url(\App\Filament\Resources\LeadResource::getUrl('edit', ['record' => $actividad->lead_id]))
                            ->button(),
                    ])
                    ->sendToDatabase($user);
            }

            $this->info("Notificadas {$items->count()} actividad(es) al usuario #{$userId}.");
        }

        return self::SUCCESS;
    }
}
