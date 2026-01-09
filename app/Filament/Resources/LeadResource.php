<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeadResource\Pages;
use App\Models\Lead;
use App\Exports\LeadsExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Database\Eloquent\Collection;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport; 
use pxlrbt\FilamentExcel\Columns\Column;

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    // Configuración del Menú
    protected static ?string $navigationLabel = 'Interesados';
    protected static ?string $navigationGroup = 'Interesados'; 
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $modelLabel = 'Interesado';
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('etapa', 'no_contactado')->count();
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::where('etapa', 'no_contactado')->count() > 0 ? 'danger' : 'success';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Prospecto')
                    ->schema([
                        Forms\Components\TextInput::make('nombre')->required(),
                        Forms\Components\TextInput::make('correo')->email(),
                        Forms\Components\TextInput::make('telefono')->tel(),
                        Forms\Components\Select::make('origen')
                            ->options([
                                'Nocnok - Sitio' => 'Nocnok',
                                'Rentas.com' => 'Rentas.com',
                                'Whatsapp' => 'Whatsapp',
                                'Llamada' => 'Llamada',
                            ]),
                        Forms\Components\Textarea::make('mensaje')
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Seguimiento')
                    ->schema([
                        Forms\Components\Select::make('etapa')
                            ->options([
                                'no_contactado' => 'No contactado',
                                'contactado' => 'Contactado',
                                'cita' => 'Cita',
                                'seguimiento' => 'Seguimiento',
                                'propuesta' => 'Propuesta',
                                'en_cierre' => 'En cierre',
                                'ganado' => 'Ganado',
                                'perdido' => 'Perdido',
                                'no_califica' => 'No califica',
                            ])
                            ->default('no_contactado')
                            ->required(),
                        
                        Forms\Components\Select::make('responsable_id')
                            ->relationship('responsable', 'name')
                            ->label('Asesor Responsable'),
                            
                        Forms\Components\TextInput::make('url_propiedad')
                            ->label('Propiedad de Interés')
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('visitar')
                                    ->icon('heroicon-m-arrow-top-right-on-square')
                                    ->url(fn ($state) => $state, shouldOpenInNewTab: true)
                            )
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            // No contactados primero
            ->defaultSort(fn ($query) => $query->orderByRaw("CASE WHEN etapa = 'no_contactado' THEN 1 ELSE 2 END")->orderBy('created_at', 'desc'))
            
            // QUERY INICIAL: Ocultar ganados/perdidos por defecto
            ->modifyQueryUsing(fn (Builder $query) => $query->whereNotIn('etapa', ['ganado', 'perdido', 'no_califica']))
            
            // BOTÓN SUPERIOR (EXPORTAR TODO CON DISEÑO)
            ->headerActions([
                Action::make('exportar_todo_bonito')
                    ->label('Descargar Reporte Oficial')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('primary')
                    ->action(function () {
                        // Descarga TODOS los leads usando tu diseño con Logo
                        return Excel::download(new LeadsExport(Lead::all()), 'Reporte_Interesados_' . date('Y-m-d') . '.xlsx');
                    }),
            ])

            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->searchable()
                    ->weight(FontWeight::Bold)
                    ->description(fn (Lead $record) => $record->correo),

                Tables\Columns\TextColumn::make('telefono')
                    ->icon('heroicon-m-phone')
                    ->url(fn ($state) => 'tel:'.$state)
                    ->searchable(),

                Tables\Columns\TextColumn::make('etapa')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'no_contactado' => 'danger',
                        'ganado' => 'success',
                        'perdido', 'no_califica' => 'gray',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state))),

                Tables\Columns\TextColumn::make('origen')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('mensaje')
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 30) return null;
                        return $state;
                    }),

                Tables\Columns\TextColumn::make('responsable.name')
                    ->label('Responsable')
                    ->placeholder('Sin asignar'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('etapa')
                    ->multiple()
                    ->options([
                        'no_contactado' => 'No contactado',
                        'contactado' => 'Contactado',
                        'cita' => 'Cita',
                        'seguimiento' => 'Seguimiento',
                        'propuesta' => 'Propuesta',
                        'en_cierre' => 'En cierre',
                        'ganado' => 'Ganado',
                        'perdido' => 'Perdido',
                        'no_califica' => 'No califica',
                    ]),
                    
                Tables\Filters\Filter::make('mostrar_todo_historial')
                    ->label('Mostrar Historial Completo')
                    ->query(fn (Builder $query) => $query->orWhereIn('etapa', ['ganado', 'perdido', 'no_califica'])),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('contactar')
                    ->label('Ya contacté')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(fn (Lead $record) => $record->update(['etapa' => 'contactado']))
                    ->visible(fn (Lead $record) => $record->etapa === 'no_contactado'),
            ])
            
            // ACCIONES MASIVAS (CHECKBOX) 
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    // Acción para exportar solo lo seleccionado con diseño
                    BulkAction::make('exportar_seleccion_bonito')
                        ->label('Exportar Selección con Logo')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function (Collection $records) {
                            return Excel::download(new LeadsExport($records), 'Seleccion_Interesados_' . date('Y-m-d') . '.xlsx');
                        })
                        ->deselectRecordsAfterCompletion()
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
        'index' => Pages\ListLeads::route('/'),
        'create' => Pages\CreateLead::route('/create'),
        'edit' => Pages\EditLead::route('/{record}/edit'),
        ];
    }
}