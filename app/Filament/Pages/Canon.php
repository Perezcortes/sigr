<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class Canon extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Canon';
    protected static ?string $navigationGroup = 'Centro de pagos'; // Mismo grupo
    protected static ?string $title = 'Gestión de Canon';
    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.pages.canon';
}