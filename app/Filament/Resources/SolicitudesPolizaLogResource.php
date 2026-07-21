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
use Illuminate\Support\HtmlString;

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
        return auth()->user()->hasAnyRole(['Administrador', 'Soporte']);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información General')
                    ->schema([
                        Forms\Components\TextInput::make('rent_id')
                            ->label('ID de Renta')
                            ->disabled(),

                        Forms\Components\TextInput::make('external_reference')
                            ->label('Referencia Externa')
                            ->disabled()
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('copiar')
                                    ->icon('heroicon-o-clipboard')
                                    ->action(fn () => null)
                            ),

                        Forms\Components\TextInput::make('status')
                            ->label('Estatus')
                            ->disabled(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('JSON Enviado')
                    ->schema([
                        Forms\Components\Placeholder::make('payload_enviado_pretty')
                            ->label('')
                            ->content(fn ($record) => self::formatJson($record?->payload_enviado))
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make('Respuesta del Webhook')
                    ->schema([
                        Forms\Components\Placeholder::make('mensaje_webhook_pretty')
                            ->label('')
                            ->content(fn ($record) => self::formatJson($record?->mensaje_webhook))
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make('Error')
                    ->schema([
                        Forms\Components\Placeholder::make('mensaje_error_pretty')
                            ->label('')
                            ->content(fn ($record) => $record?->mensaje_error
                                ? new HtmlString(
                                    '<pre class="text-sm whitespace-pre-wrap p-3 bg-red-50 dark:bg-red-950/40 text-red-700 dark:text-red-400 rounded-lg border border-red-200 dark:border-red-900">'
                                    . e($record->mensaje_error) . '</pre>'
                                )
                                : new HtmlString('<span class="text-gray-400 italic">Sin errores</span>'))
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->visible(fn ($record) => filled($record?->mensaje_error)),
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
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Referencia copiada'),

                TextColumn::make('status')
                    ->label('Estatus')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'enviado' => 'warning',
                        'procesando' => 'info',
                        'completado' => 'success',
                        'fallido' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'enviado' => 'heroicon-o-paper-airplane',
                        'procesando' => 'heroicon-o-arrow-path',
                        'completado' => 'heroicon-o-check-circle',
                        'fallido' => 'heroicon-o-x-circle',
                        default => 'heroicon-o-question-mark-circle',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estatus')
                    ->options([
                        'enviado' => 'Enviado',
                        'procesando' => 'Procesando',
                        'completado' => 'Completado',
                        'fallido' => 'Fallido',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->iconButton()
                    ->tooltip('Ver detalle'),
            ])
            ->bulkActions([
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

    /**
     * Convierte un string JSON en HTML formateado y con resaltado de sintaxis,
     * sin depender de librerías externas.
     */
    protected static function formatJson(?string $json): HtmlString
    {
        if (blank($json)) {
            return new HtmlString('<span class="text-gray-400 italic">Sin datos</span>');
        }

        $decoded = json_decode($json);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // No es JSON válido: mostrarlo tal cual, sin intentar formatear
            return new HtmlString(
                '<pre class="text-sm whitespace-pre-wrap p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">'
                . e($json) . '</pre>'
            );
        }

        $pretty = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $highlighted = preg_replace_callback(
            '/("(\\\\u[a-zA-Z0-9]{4}|\\\\[^u]|[^\\\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/',
            function (array $matches): string {
                $match = $matches[0];

                $class = match (true) {
                    str_starts_with($match, '"') && str_ends_with(rtrim($match), ':')
                        => 'text-purple-600 dark:text-purple-400 font-semibold', // llave
                    str_starts_with($match, '"')
                        => 'text-green-600 dark:text-green-400', // valor string
                    $match === 'true' || $match === 'false'
                        => 'text-orange-500 dark:text-orange-400 font-medium',
                    $match === 'null'
                        => 'text-gray-400 italic',
                    default
                        => 'text-blue-600 dark:text-blue-400', // número
                };

                return "<span class=\"{$class}\">" . e($match) . '</span>';
            },
            $pretty
        );

        return new HtmlString(
            '<pre class="text-sm leading-relaxed whitespace-pre-wrap p-3 bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 overflow-x-auto">'
            . $highlighted . '</pre>'
        );
    }
}