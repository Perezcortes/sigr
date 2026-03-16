<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Notifications\Notification;

class EstadoCuenta extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static ?string $navigationGroup = 'Centro de pagos';
    protected static ?string $navigationLabel = 'Estado de Cuenta';
    protected static ?string $title = 'Mi Estado de Cuenta';
    protected static ?int $navigationSort = 2; 

    protected static string $view = 'filament.pages.estado-de-cuenta';

    // Variables que mandaremos a la vista (Simuladas por ahora)
    public $linkStripeApartados = 'https://buy.stripe.com/mi_link_aqui';
    
    // El dinero "congelado" de rentas/ventas que aún no se cierran
    public $saldoRetenido = 15000.00; 
    
    // El dinero liberado que ya pueden usar para pagar sus regalías
    public $saldoDisponible = 4500.00; 

    public function copiarLink()
    {
        // Esta función simula la acción, el copiado real se hace con JS en la vista
        Notification::make()
            ->success()
            ->title('Link copiado al portapapeles')
            ->body('Ya puedes enviarlo a tu cliente por WhatsApp o correo.')
            ->send();
    }
}