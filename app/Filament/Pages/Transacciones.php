<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class Transacciones extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-queue-list';
    protected static ?string $navigationLabel = 'Transacciones';
    protected static ?string $navigationGroup = 'Centro de pagos'; // Mismo grupo
    protected static ?string $title = 'Registro de Transacciones';
    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.transacciones';
}