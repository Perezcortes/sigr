<?php

namespace App\Filament\Resources\AdministrationResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class TicketsRelationManager extends RelationManager
{
    protected static string $relationship = 'tickets';
    protected static ?string $title = 'Reportes e Incidencias';
    protected static ?string $icon = 'heroicon-o-exclamation-triangle';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\Select::make('tipo')
                        ->options([
                            'peticion' => 'Petición / Solicitud',
                            'incidencia' => 'Incidencia / Reporte de Daño',
                        ])
                        ->required(),
                    Forms\Components\TextInput::make('titulo')
                        ->required()
                        ->placeholder('Ej. Fuga de agua...'),
                ]),
                Forms\Components\Textarea::make('descripcion')
                    ->label('Detalles')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tipo')
                    ->badge()
                    ->icon(fn (string $state): string => match ($state) {
                        'incidencia' => 'heroicon-o-fire',
                        'peticion' => 'heroicon-o-hand-raised',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->color(fn(string $state) => match($state){
                        'incidencia' => 'danger',
                        'peticion' => 'info',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('titulo')
                    ->weight('bold')
                    ->searchable()
                    ->description(fn ($record) => substr($record->descripcion ?? '', 0, 40) . '...'),

                Tables\Columns\TextColumn::make('estatus')
                    ->badge()
                    ->color(fn(string $state) => match($state){
                        'nueva' => 'gray',
                        'en_proceso' => 'warning',
                        'completada' => 'success',
                    }),
                
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Autor')
                    ->icon('heroicon-o-user-circle')
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Crear Reporte')
                    ->icon('heroicon-o-plus-circle')
                    ->mutateFormDataUsing(function (array $data) {
                        $data['user_id'] = auth()->id();
                        $data['estatus'] = 'nueva';
                        return $data;
                    })
                    // Visible para Admin e Inquilino
                    ->visible(fn () => auth()->user()->hasRole(['Administrador', 'Asesor', 'Cliente']) || auth()->user()->is_tenant), 
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->visible(fn ($record) => auth()->user()->hasRole(['Administrador', 'Asesor']) || ($record->estatus === 'nueva' && auth()->id() === $record->user_id)),
                
                Tables\Actions\Action::make('responder')
                    ->label('Atender')
                    ->icon('heroicon-o-chat-bubble-bottom-center-text')
                    ->color('warning')
                    ->visible(fn () => auth()->user()->hasRole(['Administrador', 'Asesor']))
                    ->form([
                        Forms\Components\Select::make('estatus')
                            ->options(['en_proceso' => 'En Proceso', 'completada' => 'Completada'])
                            ->required(),
                        Forms\Components\Textarea::make('comentarios_admin')
                            ->label('Respuesta / Comentarios'),
                    ])
                    ->action(fn ($record, array $data) => $record->update($data)),
            ]);
    }
}