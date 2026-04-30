<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Concerns\HasPortalFlagUserList;
use App\Filament\Resources\PropietariosVendedoresResource\Pages;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PropietariosVendedoresResource extends Resource
{
    use HasPortalFlagUserList;

    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-home-modern';

    protected static ?string $navigationLabel = 'Propietarios / Vendedores';

    protected static ?string $modelLabel = 'Propietario / Vendedor';

    protected static ?string $pluralModelLabel = 'Propietarios / Vendedores';

    protected static ?string $navigationGroup = 'Ventas';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'propietarios-vendedores';

    protected static function portalFlagColumn(): string
    {
        return 'is_seller';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Correo')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('mobile')
                    ->label('Móvil')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('telefono')
                    ->label('Teléfono')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('assignedAsesor.name')
                    ->label('Asesor')
                    ->placeholder('—')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Alta')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Cuenta activa'),
            ])
            ->actions([
                Tables\Actions\Action::make('editarUsuario')
                    ->label('Editar')
                    ->icon('heroicon-m-pencil-square')
                    ->url(fn (User $record): string => UserResource::getUrl('edit', ['record' => $record]))
                    ->visible(fn (User $record): bool => UserResource::canEdit($record)),
            ])
            ->bulkActions([])
            ->defaultSort('name');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPropietariosVendedores::route('/'),
        ];
    }
}
