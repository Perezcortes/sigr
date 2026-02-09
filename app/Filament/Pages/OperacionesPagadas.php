<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class OperacionesPagadas extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-check-circle';
    protected static ?string $navigationLabel = 'Operaciones Pagadas';
    protected static ?string $navigationGroup = 'Centro de pagos'; // Mismo grupo
    protected static ?string $title = 'Historial de Pagos';
    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.operaciones-pagadas';
}