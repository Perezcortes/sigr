<?php

namespace App\Filament\Resources\PayableOperationResource\Pages;

use App\Filament\Resources\PayableOperationResource;
use Filament\Resources\Pages\Page;
use App\Models\PayableOperation;
use Filament\Notifications\Notification;
use Filament\Actions\Action;

class CheckoutPagos extends Page
{
    protected static string $resource = PayableOperationResource::class;

    protected static string $view = 'filament.resources.payable-operation-resource.pages.checkout-pagos';

    protected static ?string $title = 'Centro de Pagos (Check-Out)';

    public $operaciones = [];
    public $totalPagar = 0;

    public function mount()
    {
        // Recibir los IDs de la URL (ej. ?operaciones=5,8,12)
        $ids = request()->query('operaciones');

        if (!$ids) {
            $this->redirect(PayableOperationResource::getUrl('index'));
            return;
        }

        $idsArray = explode(',', $ids);

        // Buscar las operaciones en la BD (solo las pendientes del usuario actual)
        $this->operaciones = PayableOperation::whereIn('id', $idsArray)
            ->where('estatus', 'pendiente de pago')
            ->where('user_id', auth()->id())
            ->get();

        if ($this->operaciones->isEmpty()) {
            Notification::make()->warning()->title('Las operaciones seleccionadas ya fueron pagadas o no son válidas.')->send();
            $this->redirect(PayableOperationResource::getUrl('index'));
            return;
        }

        // Calcular el total exacto a cobrar
        $this->totalPagar = $this->operaciones->sum('regalia');
    }

    // --- ACCIONES DE PAGO (SIMULADAS REVISAR DESPUES) ---

    public function pagarConSaldoAction(): Action
    {
        return Action::make('pagarConSaldo')
            ->label('Pagar con Saldo a Favor')
            ->icon('heroicon-o-wallet')
            ->color('primary')
            ->requiresConfirmation()
            ->modalHeading('Confirmar pago con Saldo')
            ->modalDescription("Se descontarán $" . number_format($this->totalPagar, 2) . " de tu saldo virtual a favor.")
            ->action(function () {
                $this->marcarComoPagadas('Saldo a favor');
            });
    }

    public function pagarConTarjetaAction(): Action
    {
        return Action::make('pagarConTarjeta')
            ->label('Pagar con Tarjeta (Stripe)')
            ->icon('heroicon-o-credit-card')
            ->color('success')
            ->action(function () {
                // Aquí iría la redirección real a Stripe Checkout en el futuro
                Notification::make()->info()->title('Simulación de redirección a Stripe...')->send();
                $this->marcarComoPagadas('Tarjeta de Crédito/Débito');
            });
    }

    public function pagarConTransferenciaAction(): Action
    {
        return Action::make('pagarConTransferencia')
            ->label('Generar Cuenta STP (Transferencia)')
            ->icon('heroicon-o-building-library')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Transferencia SPEI (STP)')
            ->modalDescription("El sistema generará una CLABE interbancaria única a tu nombre por la cantidad exacta de $" . number_format($this->totalPagar, 2) . ". Tienes 24 horas para realizar el depósito.")
            ->modalSubmitActionLabel('Generar CLABE')
            ->action(function () {
                // Aquí iría la conexión con STP
                Notification::make()->success()->title('CLABE generada (Simulación). El pago se reflejará automáticamente al transferir.')->send();
                $this->redirect(PayableOperationResource::getUrl('index'));
            });
    }

    // Función interna para acreditar el pago en la BD
    private function marcarComoPagadas($metodo)
    {
        foreach ($this->operaciones as $op) {
            $op->update([
                'estatus' => 'pagada',
                'fecha_pago' => now(),
                // Aquí en el futuro se podría guardar el método de pago en la tabla
            ]);
        }

        Notification::make()->success()->title("¡Pago exitoso mediante $metodo!")->send();
        $this->redirect(PayableOperationResource::getUrl('index'));
    }
}