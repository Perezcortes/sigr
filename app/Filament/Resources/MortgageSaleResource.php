<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MortgageSaleResource\Pages;
use App\Models\Sale;
use App\Support\Filament\ScopesByOfficeAndAdvisor;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MortgageSaleResource extends Resource
{
    protected static ?string $model = Sale::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationLabel = 'Mis hipotecas';

    protected static ?string $modelLabel = 'Venta con hipoteca';

    protected static ?string $pluralModelLabel = 'Ventas con hipoteca';

    protected static ?string $navigationGroup = 'Ventas';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'mis-hipotecas';

    public static function canViewAny(): bool
    {
        return SaleResource::canViewAny();
    }

    public static function canView(Model $record): bool
    {
        return SaleResource::canEdit($record);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->where('requiere_hipoteca', true);

        return ScopesByOfficeAndAdvisor::scopeSalesForFilament($query, auth()->user());
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('fecha_inicio')
                    ->date('d/m/Y')
                    ->label('Fecha')
                    ->sortable(),
                TextColumn::make('nombre_cliente_principal')
                    ->label('Comprador')
                    ->state(fn (Sale $record): string => trim(($record->comprador_nombres ?? '').' '.($record->comprador_ap_paterno ?? '')))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('comprador_nombres', 'like', "%{$search}%")
                            ->orWhere('comprador_ap_paterno', 'like', "%{$search}%");
                    }),
                TextColumn::make('monto_operacion')
                    ->money('MXN')
                    ->label('Monto')
                    ->sortable(),
                TextColumn::make('estatus_operacion')
                    ->label('Estatus venta')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Cerrada' => 'success',
                        'Cancelada' => 'danger',
                        'En búsqueda' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('estatus_hipoteca')
                    ->label('Estatus hipoteca')
                    ->badge()
                    ->placeholder('—')
                    ->color(fn (?string $state): string => match ($state) {
                        'Aprobada' => 'success',
                        'Rechazada' => 'danger',
                        'Ingresada a Bancos' => 'warning',
                        'Firmada' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('hipoteca_banco')
                    ->label('Banco')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->actions([
                Tables\Actions\Action::make('verHipoteca')
                    ->label('Ver hipoteca')
                    ->icon('heroicon-m-building-library')
                    ->url(fn (Sale $record): string => SaleResource::getEditUrlWithHipotecaTab($record)),
            ])
            ->bulkActions([])
            ->defaultSort('fecha_inicio', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMortgageSales::route('/'),
        ];
    }
}
