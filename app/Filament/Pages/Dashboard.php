<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Resumen General'; 
    protected static ?string $navigationLabel = 'Inicio'; 
    protected static ?string $navigationGroup = 'Dashboard'; 
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?int $navigationSort = -1;
}

