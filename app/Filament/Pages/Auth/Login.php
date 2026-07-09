<?php

namespace App\Filament\Pages\Auth;

use Filament\Actions\Action;
use Filament\Pages\Auth\Login as BaseLogin;

class Login extends BaseLogin
{
    // Prueba: el <main> de SimplePage limita a max-w-lg por default — lo ampliamos
    // para descartar que sea ese el contenedor topando el ancho de la tarjeta.
    protected ?string $maxWidth = 'max-w-full';

    public function getView(): string
    {
        return 'filament.pages.auth.login';
    }

    // Botón naranja de marca en vez del azul primary por default
    protected function getAuthenticateFormAction(): Action
    {
        return parent::getAuthenticateFormAction()->color('warning');
    }
}
