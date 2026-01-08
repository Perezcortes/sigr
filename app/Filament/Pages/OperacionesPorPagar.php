<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class OperacionesPorPagar extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationLabel = 'Operaciones por pagar';
    
    protected static ?string $navigationGroup = 'Centro de pagos'; 
    
    protected static ?string $title = 'Operaciones Pendientes';
    protected static ?int $navigationSort = 1; // Saldrá primero

    protected static string $view = 'filament.pages.operaciones-por-pagar';
}