<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\PayableOperation;
use Filament\Notifications\Notification;

class CheckPagosVencidos
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        // 1. Si no está logueado o es Administrador, lo dejamos pasar libremente
        if (!$user || $user->hasRole('Administrador')) {
            return $next($request);
        }

        // 2. Buscamos si tiene alguna deuda vencida (fecha_vencimiento ya pasó)
        $tieneDeudasVencidas = PayableOperation::where('user_id', $user->id)
            ->where('estatus', 'pendiente de pago')
            ->where('fecha_vencimiento', '<', now())
            ->exists();

        // 3. Si tiene deudas vencidas, aplicamos el bloqueo
        if ($tieneDeudasVencidas) {
            
            // Rutas a las que SÍ lo dejamos entrar (Centro de Pagos y Cerrar sesión)
            $rutasPermitidas = [
                'filament.admin.resources.payable-operations.index',
                'filament.admin.resources.payable-operations.checkout',
                'filament.admin.pages.estado-de-cuenta',
                'filament.admin.auth.logout',
            ];

            $rutaActual = $request->route()->getName();

            // Si intenta entrar a Rentas, Ventas, Dashboard, etc... No pasará y verá la alerta
            if (!in_array($rutaActual, $rutasPermitidas)) {
                
                Notification::make()
                    ->danger()
                    ->title('Cuenta Suspendida Temporalmente')
                    ->body('Tienes operaciones con más de 10 días de atraso. Por favor, realiza el pago de tus regalías para restablecer tu acceso al sistema.')
                    ->persistent() // La alerta no se borra sola
                    ->send();

                // Lo enviamos directo a la caja a pagar
                return redirect()->route('filament.admin.resources.payable-operations.index');
            }
        }

        return $next($request);
    }
}