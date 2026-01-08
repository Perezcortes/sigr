<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class EstadoDeCuenta extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationLabel = 'Estado de Cuenta';
    protected static ?string $navigationGroup = 'Centro de pagos'; // Mismo grupo
    protected static ?string $title = 'Estado de Cuenta General';
    protected static ?int $navigationSort = 5;

    protected static string $view = 'filament.pages.estado-de-cuenta';
}