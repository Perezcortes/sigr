<?php

namespace App\Filament\Resources\AdministrationResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ServicesRelationManager extends RelationManager
{
    protected static string $relationship = 'services';
    protected static ?string $title = 'Pagos y Servicios';
    protected static ?string $icon = 'heroicon-o-currency-dollar';

    // Permitir botones
    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nombre')
                    ->label('Concepto')
                    ->placeholder('Ej. Mantenimiento, Luz, Agua')
                    ->required()
                    ->columnSpan(2),
                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\TextInput::make('monto')
                        ->numeric()
                        ->prefix('$')
                        ->required(),
                    Forms\Components\Select::make('frecuencia')
                        ->options([
                            'mensual' => 'Mensual',
                            'bimestral' => 'Bimestral',
                            'unico' => 'Pago Único',
                        ])
                        ->required(),
                ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->weight('bold')
                    ->icon('heroicon-o-bolt')
                    ->description('Pago programado'),
                Tables\Columns\TextColumn::make('monto')
                    ->money('MXN')
                    ->weight('black')
                    ->color('success')
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Large),
                Tables\Columns\TextColumn::make('frecuencia')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'mensual' => 'info',
                        'unico' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Agregar Cargo')
                    ->icon('heroicon-o-plus')
                    // SOLO ADMIN crea
                    ->visible(fn () => auth()->user()->hasRole(['Administrador', 'Asesor'])), 
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->visible(fn () => auth()->user()->hasRole(['Administrador', 'Asesor'])),
                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->visible(fn () => auth()->user()->hasRole(['Administrador', 'Asesor'])),
            ]);
    }
}