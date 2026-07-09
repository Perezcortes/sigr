<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SolicitudesPolizaLogResource\Pages;
use App\Models\SolicitudesPolizaLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;

class SolicitudesPolizaLogResource extends Resource
{
    protected static ?string $model = SolicitudesPolizaLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Solicitudes Póliza';

    protected static ?string $modelLabel = 'Log de Solicitud';

    protected static ?string $pluralModelLabel = 'Logs API Póliza';

    protected static ?string $navigationGroup = 'Administración';

    protected static ?int $navigationSort = 3; 

    public static function canViewAny(): bool
    {
        // Solo Administradores y Gerentes deberían ver los logs de solicitudes de póliza
        return auth()->user()->hasAnyRole(['Administrador', 'Soporte']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('rent_id')
                    ->label('ID de Renta')
                    ->disabled(),
                Forms\Components\TextInput::make('external_reference')
                    ->label('Referencia Externa')
                    ->disabled(),
                Forms\Components\TextInput::make('status')
                    ->label('Estatus')
                    ->disabled(),
                Forms\Components\Textarea::make('payload_enviado')
                    ->label('JSON Enviado')
                    ->disabled()
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('mensaje_webhook')
                    ->label('Respuesta del Webhook')
                    ->disabled()
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('mensaje_error')
                    ->label('Error (si falló)')
                    ->disabled()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('rent.id') 
                    ->label('Renta ID')
                    ->sortable(),
                TextColumn::make('external_reference')
                    ->label('Referencia')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Estatus')
                    ->badge()
                    ->colors([
                        'warning' => 'enviado',
                        'info' => 'procesando',
                        'success' => 'completado',
                        'danger' => 'fallido',
                    ]),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(), 
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSolicitudesPolizaLogs::route('/'),
            'view' => Pages\ViewSolicitudesPolizaLog::route('/{record}'),
        ];
    }
}