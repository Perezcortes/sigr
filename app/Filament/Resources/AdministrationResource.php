<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdministrationResource\Pages;
use App\Models\Rent;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AdministrationResource extends Resource
{
    protected static ?string $model = Rent::class;

    protected static ?string $recordRouteKeyName = 'hash_id';

    protected static ?string $navigationLabel = 'Mis Administraciones';

    protected static ?string $modelLabel = 'Administración';

    protected static ?string $pluralModelLabel = 'Administraciones';

    protected static ?string $navigationGroup = 'Rentas';

    protected static ?string $slug = 'mis-administraciones';

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?int $navigationSort = 4;

    public static function canCreate(): bool
    {
        // Desactivamos la creación manual porque se generan solas desde las Rentas
        return false;
    }

    public static function resolveRecordRouteBinding(int|string $key): ?Model
    {
        // Usamos la función findByHash en Trait HasHashId
        return app(static::getModel())->findByHash($key);
    }

    public static function getEloquentQuery(): Builder
    {
        // Mostramos solo rentas administradas por el agente.
        // El estatus (activa / por vencer / vencida) se controla desde tabs en el listado.
        $query = parent::getEloquentQuery()
            ->where('is_administrada_por_agente', true);

        $user = Auth::user();

        if (! $user) {
            return $query;
        }

        if ($user->hasRole('Administrador')) {
            return $query;
        }
        if ($user->hasRole('Agente')) {
            return $query->where('asesor_id', $user->id);
        }
        if ($user->hasRole('Cliente')) {
            return $query->whereHas('tenant', fn ($q) => $q->where('user_id', $user->id));
        }
        if ($user->hasRole('Propietario')) {
            return $query->whereHas('owner', fn ($q) => $q->where('user_id', $user->id));
        }

        return $query;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // 1. FOTO DE PORTADA
                Tables\Columns\ImageColumn::make('portada_inmueble')
                    ->label('')
                    ->state(function ($record) {
                        $property = $record->property;
                        if (! $property) {
                            return null;
                        }
                        // Busca la portada o la primera imagen disponible
                        $img = $property->images->where('is_portada', true)->first()
                            ?? $property->images->first();

                        return $img ? $img->path_file : null;
                    })
                    ->disk('public')
                    ->width(120)
                    ->height(80)
                    ->extraImgAttributes([
                        'class' => 'object-cover rounded-lg shadow-sm border border-gray-200',
                    ]),

                // 2. DETALLES DEL INMUEBLE
                Tables\Columns\TextColumn::make('property.tipo_inmueble')
                    ->label('Propiedad')
                    ->weight('bold')
                    ->description(function ($record) {
                        $p = $record->property;
                        if (! $p) {
                            return 'Sin dirección';
                        }

                        return trim(($p->calle ?? '').' '.($p->numero_exterior ?? '').', '.($p->colonia ?? ''));
                    })
                    ->wrap(),

                // 3. RENTA MENSUAL (formato 15,000.00 MXN)
                Tables\Columns\TextColumn::make('monto')
                    ->label('Renta')
                    ->formatStateUsing(fn ($state) => $state !== null && $state !== '' ? number_format((float) $state, 2, '.', ',').' MXN' : '-')
                    ->sortable()
                    ->weight('black')
                    ->color('success')
                    ->state(fn ($record) => $record->monto ?? $record->property->precio_renta ?? 0),

                // 4. VENCIMIENTO
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Vence')
                    ->date('d M Y')
                    ->badge()
                    ->color(fn ($state) => $state && $state < now() ? 'danger' : 'gray')
                    ->sortable()
                    ->placeholder('Por configurar'),
            ])
            ->actions([
                // BOTÓN ÚNICO PARA ENTRAR AL DASHBOARD
                Tables\Actions\ViewAction::make()
                    ->label('Entrar')
                    ->button()
                    ->color('primary')
                    ->icon('heroicon-o-arrow-right-end-on-rectangle'),
            ])
            ->actionsColumnLabel('ACCIONES')
            ->paginated([10, 25, 50]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdministrations::route('/'),
            'view' => Pages\ViewAdministration::route('/{record}'),
        ];
    }
}
