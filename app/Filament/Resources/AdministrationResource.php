<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdministrationResource\Pages;
use App\Filament\Resources\AdministrationResource\RelationManagers\MessagesRelationManager;
use App\Filament\Resources\AdministrationResource\RelationManagers\ServicesRelationManager;
use App\Filament\Resources\AdministrationResource\RelationManagers\TicketsRelationManager;
use App\Models\Rent;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class AdministrationResource extends Resource
{
    protected static ?string $model = Rent::class;

    protected static ?string $recordRouteKeyName = 'hash_id';
    protected static ?string $navigationLabel = 'Mis Administraciones';
    protected static ?string $modelLabel = 'Administración';
    protected static ?string $pluralModelLabel = 'Administraciones';
    protected static ?string $navigationGroup = 'Mis Administraciones';
    protected static ?string $slug = 'mis-administraciones';
    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?int $navigationSort = 1;

    public static function resolveRecordRouteBinding(int | string $key): ?\Illuminate\Database\Eloquent\Model
    {
        return app(static::getModel())->resolveRouteBinding($key);
    }
    
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->where('estatus', 'activa'); 
        $user = Auth::user();

        if (! $user) return $query;

        if ($user->hasRole('Administrador')) return $query;
        if ($user->hasRole('Asesor')) return $query->where('asesor_id', $user->id);
        if ($user->hasRole('Cliente')) return $query->whereHas('tenant', fn($q) => $q->where('user_id', $user->id));
        if ($user->hasRole('Propietario')) return $query->whereHas('owner', fn($q) => $q->where('user_id', $user->id));
        
        return $query;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('property.nombre')
                    ->label('Inmueble')
                    ->weight('bold')
                    ->icon('heroicon-o-home-modern')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tenant.nombre_completo')
                    ->label('Inquilino')
                    ->icon('heroicon-o-user'),
                Tables\Columns\TextColumn::make('fecha_inicio')
                    ->date('d M Y')
                    ->icon('heroicon-o-calendar')
                    ->label('Inicio'),
                Tables\Columns\TextColumn::make('fecha_fin')
                    ->date('d M Y')
                    ->icon('heroicon-o-flag')
                    ->label('Fin')
                    ->color('danger'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Entrar')
                    ->button()
                    ->color('primary')
                    ->icon('heroicon-o-arrow-right-end-on-rectangle'),
            ]);
    }

    public static function getRelations(): array
    {
        $relations = [
            MessagesRelationManager::class, 
        ];

        $user = Auth::user();
        if (! $user) return $relations;

        // Todos ven Servicios y Tickets (Inquilino/Propietario solo lectura en servicios)
        $hasAccess = $user->hasRole(['Administrador', 'Asesor', 'Gerente', 'Cliente', 'Propietario']) 
                     || $user->is_tenant 
                     || $user->is_owner; 

        if ($hasAccess) {
            $relations[] = ServicesRelationManager::class;
            $relations[] = TicketsRelationManager::class;
        }

        return $relations;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdministrations::route('/'),
            'view' => Pages\ViewAdministration::route('/{record}'), 
        ];
    }
}