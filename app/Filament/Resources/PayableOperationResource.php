<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PayableOperationResource\Pages\CheckoutPagos;
use App\Filament\Resources\PayableOperationResource\Pages;
use App\Models\PayableOperation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PayableOperationResource extends Resource
{
    protected static ?string $model = PayableOperation::class;

    protected static ?string $navigationLabel = 'Operaciones por pagar'; 
    protected static ?string $modelLabel = 'Operación por pagar';
    protected static ?string $pluralModelLabel = 'Operaciones por pagar';
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Centro de pagos'; 
    protected static ?int $navigationSort = 1;

    // Solo mostramos lo que le corresponde a cada quien
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if ($user && !$user->hasRole('Administrador')) {
            $query->where('user_id', $user->id);
        }

        return $query;
    }

   // Bloqueamos crear, editar y eliminar. 
    public static function canCreate(): bool { return false; }
    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool { return false; }
    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool { return false; }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('payable_type')
                    ->label('Tipo')
                    ->formatStateUsing(fn (string $state): string => str_contains($state, 'Rent') ? 'Renta' : 'Venta')
                    ->badge()
                    ->color(fn (string $state): string => str_contains($state, 'Rent') ? 'info' : 'warning'),
                
                Tables\Columns\TextColumn::make('nombre_cliente')
                    ->label('Cliente')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('fecha_firma')
                    ->label('Fecha Firma')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('monto_operacion')
                    ->label('Monto Operación')
                    ->money('MXN')
                    ->sortable(),

                Tables\Columns\TextColumn::make('monto_comision')
                    ->label('Comisión Agente')
                    ->money('MXN'),

                Tables\Columns\TextColumn::make('regalia')
                    ->label('Regalía a Pagar (12%)')
                    ->money('MXN')
                    ->weight('bold')
                    ->color('danger'),

                Tables\Columns\TextColumn::make('estatus')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pendiente de pago' => 'danger',
                        'pagada' => 'success',
                    }),
            ])
            ->filters([
                // Pestañas rápidas para filtrar
                Tables\Filters\SelectFilter::make('estatus')
                    ->options([
                        'pendiente de pago' => 'Pendientes',
                        'pagada' => 'Pagadas',
                    ])
                    ->default('pendiente de pago'),
            ])
            ->actions([
                // BOTÓN DE PAGO INDIVIDUAL
                Tables\Actions\Action::make('pagar_individual')
                    ->label('Pagar')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('success')
                    ->visible(fn ($record) => $record->estatus === 'pendiente de pago')
                    ->url(fn ($record) => CheckoutPagos::getUrl(['operaciones' => $record->id])), // Pasa el ID en la URL
            ])
            ->bulkActions([
                // BOTÓN DE PAGO MÚLTIPLE
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('pagar_multiples')
                        ->label('Pagar Seleccionadas')
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        // Solo permite pagar las que están pendientes
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, Tables\Actions\BulkAction $action) {
                            $pendientes = $records->where('estatus', 'pendiente de pago')->pluck('id')->toArray();
                            
                            if (empty($pendientes)) {
                                \Filament\Notifications\Notification::make()->warning()->title('No hay operaciones pendientes seleccionadas.')->send();
                                return;
                            }

                            // Redirige al checkout pasando todos los IDs separados por comas
                            redirect(CheckoutPagos::getUrl(['operaciones' => implode(',', $pendientes)]));
                        })
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePayableOperations::route('/'),
            'checkout' => Pages\CheckoutPagos::route('/checkout'), 
        ];
    }
}