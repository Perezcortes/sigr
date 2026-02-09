<?php

namespace App\Filament\Resources\AdministrationResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Forms\Form;

class MessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';
    protected static ?string $title = 'Centro de Mensajes';
    protected static ?string $icon = 'heroicon-o-chat-bubble-left-right';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Textarea::make('cuerpo')
                    ->required()
                    ->label('Escribe tu mensaje...')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('cuerpo')
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\TextColumn::make('user.name')
                            ->weight('bold')
                            ->color(fn ($record) => $record->user->hasRole('Administrador') ? 'primary' : 'gray')
                            ->icon(fn ($record) => $record->user->hasRole('Administrador') ? 'heroicon-o-shield-check' : 'heroicon-o-user'),
                        
                        Tables\Columns\TextColumn::make('created_at')
                            ->dateTime('D d M, H:i')
                            ->color('gray')
                            ->size(Tables\Columns\TextColumn\TextColumnSize::ExtraSmall)
                            ->alignEnd(),
                    ]),
                    
                    Tables\Columns\TextColumn::make('cuerpo')
                        ->extraAttributes([
                            'class' => 'bg-gray-50 dark:bg-gray-800 p-3 rounded-lg mt-1 text-sm border border-gray-200 dark:border-gray-700',
                        ]), 

                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\TextColumn::make('visto')
                            ->formatStateUsing(fn (bool $state) => $state ? 'Visto' : 'Enviado')
                            ->color(fn (bool $state) => $state ? 'success' : 'gray')
                            ->icon(fn (bool $state) => $state ? 'heroicon-o-check-circle' : 'heroicon-o-check')
                            ->size(Tables\Columns\TextColumn\TextColumnSize::ExtraSmall),
                    ]),
                ])->space(2),
            ])
            ->contentGrid([
                'md' => 1, 
                'xl' => 1,
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Enviar Mensaje')
                    ->icon('heroicon-o-paper-airplane')
                    ->visible(true)
                    ->mutateFormDataUsing(function (array $data) {
                        $data['user_id'] = auth()->id();
                        $data['visto'] = false;
                        return $data;
                    })
                    ->slideOver(), 
            ])
            ->paginated([5, 10, 25]); 
    }
}