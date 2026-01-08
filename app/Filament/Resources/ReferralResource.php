<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReferralResource\Pages;
use App\Models\Referral;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ReferralResource extends Resource
{
    protected static ?string $model = Referral::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-plus'; // Icono de agregar usuario
    protected static ?string $navigationLabel = 'Intetesados';
    protected static ?string $modelLabel = 'Interesado';
    protected static ?string $pluralModelLabel = 'Interesados';
    protected static ?string $navigationGroup = 'Interesados';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Lead')
                    ->description('Datos recibidos desde la Aplicación Móvil')
                    ->schema([
                        Forms\Components\TextInput::make('nombre')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('correo')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('telefono')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('origen')
                            ->label('Fuente / Origen')
                            ->placeholder('Ej. App Móvil, Web'),
                        Forms\Components\TextInput::make('url_propiedad')
                            ->label('URL Propiedad de Interés')
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Gestión Interna')
                    ->schema([
                        Forms\Components\Select::make('responsable_id')
                            ->relationship('responsable', 'name')
                            ->label('Asesor Responsable')
                            ->searchable()
                            ->preload(),
                        
                        Forms\Components\Select::make('status')
                            ->options([
                                'nuevo' => 'Nuevo',
                                'contactado' => 'Contactado',
                                'descartado' => 'Descartado',
                                'venta' => 'Convertido a Venta',
                            ])
                            ->default('nuevo')
                            ->required(),
                            
                        Forms\Components\KeyValue::make('payload_original')
                            ->label('JSON Original (Solo lectura)')
                            ->disabled() // Para que solo sea informativo
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i')
                    ->label('Fecha Recepción')
                    ->sortable(),
                Tables\Columns\TextColumn::make('nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('telefono')
                    ->icon('heroicon-m-phone'),
                Tables\Columns\TextColumn::make('origen')
                    ->badge(),
                Tables\Columns\TextColumn::make('responsable.name')
                    ->label('Asignado a')
                    ->placeholder('Sin asignar'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'nuevo' => 'info',
                        'contactado' => 'warning',
                        'venta' => 'success',
                        'descartado' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'nuevo' => 'Nuevo',
                        'contactado' => 'Contactado',
                        'descartado' => 'Descartado',
                        'venta' => 'Venta',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->tooltip('Editar Referido'),
                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->tooltip('Eliminar Referido'),
            ])
            ->actionsColumnLabel('ACCIONES')
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReferrals::route('/'),
            'create' => Pages\CreateReferral::route('/create'),
            'edit' => Pages\EditReferral::route('/{record}/edit'),
        ];
    }
}