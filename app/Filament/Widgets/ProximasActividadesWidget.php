<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\LeadResource;
use App\Models\LeadActivity;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class ProximasActividadesWidget extends BaseWidget
{
    protected static ?string $heading = 'Próximas actividades';

    protected static ?int $sort = 10;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $user = auth()->user();

        return $table
            ->query(
                LeadActivity::query()
                    ->with(['lead', 'user'])
                    ->where('completada', false)
                    ->where('fecha', '>=', now()->startOfDay()->toDateString())
                    ->where('fecha', '<=', now()->addDays(7)->toDateString())
                    ->when(
                        $user->hasRole('Agente'),
                        fn (Builder $q) => $q->where('user_id', $user->id)
                    )
                    ->when(
                        $user->hasRole('Gerente'),
                        fn (Builder $q) => $q->whereHas(
                            'user',
                            fn (Builder $u) => $u->where('office_id', $user->office_id)
                        )
                    )
                    // Administrador: sin restricción adicional
                    ->orderBy('fecha')
                    ->orderBy('hora')
            )
            ->columns([
                Tables\Columns\TextColumn::make('fecha')
                    ->label('Fecha')
                    ->date('D d/m/Y')
                    ->sortable()
                    ->color(fn (LeadActivity $record): string => $record->fecha->isToday() ? 'warning' : 'gray'),

                Tables\Columns\TextColumn::make('hora')
                    ->label('Hora')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('lead.nombre')
                    ->label('Interesado')
                    ->searchable()
                    ->url(fn (LeadActivity $record): ?string =>
                        $record->lead
                            ? LeadResource::getUrl('edit', ['record' => $record->lead_id])
                            : null
                    )
                    ->color('primary'),

                Tables\Columns\TextColumn::make('descripcion')
                    ->label('Actividad')
                    ->limit(60)
                    ->wrap(),

                // Columna visible solo para Admin y Gerente para identificar de quién es cada actividad
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Agente')
                    ->placeholder('—')
                    ->badge()
                    ->color(fn (LeadActivity $record): string =>
                        $record->user_id === auth()->id() ? 'primary' : 'gray'
                    )
                    ->visible(fn (): bool => auth()->user()->hasAnyRole(['Administrador', 'Gerente'])),
            ])
            ->actions([
                Tables\Actions\Action::make('completar')
                    ->label('Marcar realizada')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->action(fn (LeadActivity $record) => $record->update(['completada' => true]))
                    ->requiresConfirmation()
                    ->modalHeading('¿Marcar como realizada?')
                    ->modalDescription(fn (LeadActivity $record) => $record->descripcion)
                    // Solo puede marcar la suya (o Admin/Gerente las de su oficina)
                    ->visible(fn (LeadActivity $record): bool =>
                        auth()->user()->hasRole('Administrador')
                        || (auth()->user()->hasRole('Gerente') && $record->user && $record->user->office_id === auth()->user()->office_id)
                        || $record->user_id === auth()->id()
                    ),
            ])
            ->emptyStateHeading('Sin actividades próximas')
            ->emptyStateDescription('Agrega seguimientos desde el perfil de cada interesado.')
            ->emptyStateIcon('heroicon-o-calendar-days')
            ->paginated(false);
    }
}
