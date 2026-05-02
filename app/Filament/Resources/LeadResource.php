<?php

namespace App\Filament\Resources;

use App\Enums\LeadCanal;
use App\Exports\LeadsExport;
use App\Filament\Resources\LeadResource\Pages;
use App\Models\Lead;
use App\Models\WhatsappInstance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\WhatsappMessage;
use WallaceMartinss\FilamentEvolution\Enums\StatusConnectionEnum;
use WallaceMartinss\FilamentEvolution\Services\WhatsappService;

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
                Forms\Components\Grid::make(2)->schema([

                    // COLUMNA IZQUIERDA (Perfil del Contacto)
                    Forms\Components\Group::make()->columnSpan(1)->schema([

                        Forms\Components\Section::make()
                            ->schema([
                                Forms\Components\TextInput::make('nombre')
                                    ->label('Nombre del Contacto')
                                    ->required()
                                    ->columnSpanFull(),

                                Forms\Components\Select::make('etapa')
                                    ->options([
                                        'nuevo' => 'Nuevo',
                                        'contactado' => 'Contactado',
                                        'cita' => 'Cita',
                                        'en_proceso' => 'En proceso',
                                        'ganado' => 'Ganado',
                                        'perdido' => 'Perdido',
                                    ])
                                    ->default('nuevo')
                                    ->required()
                                    ->columnSpanFull(),

                                Forms\Components\Select::make('responsable_id')
                                    ->relationship('responsable', 'name')
                                    ->label('Asignado a')
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('correo')
                                    ->email()
                                    ->prefixIcon('heroicon-m-envelope')
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('telefono')
                                    ->tel()
                                    ->prefixIcon('heroicon-m-phone')
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('presupuesto')
                                    ->label('Presupuesto')
                                    ->numeric()
                                    ->prefix('$')
                                    ->columnSpanFull(),

                                Forms\Components\Select::make('tipo_cliente')
                                    ->label('Tipo de cliente')
                                    ->options([
                                        'inquilino' => 'Inquilino',
                                        'arrendador' => 'Arrendador',
                                        'comprador' => 'Comprador',
                                        'vendedor' => 'Vendedor',
                                        'NA' => 'NA',
                                    ])->required()
                                    ->columnSpanFull(),

                                Forms\Components\Select::make('canal')
                                    ->label('Canal')
                                    ->options(collect(LeadCanal::cases())->mapWithKeys(
                                        fn (LeadCanal $c): array => [$c->value => $c->getLabel()]
                                    )->all())
                                    ->required()
                                    ->native(false)
                                    ->columnSpanFull(),

                                Forms\Components\Select::make('origen')
                                    ->label('Origen')
                                    ->options([
                                        'Nocnok' => 'Nocnok - Sitio',
                                        'Rentas.com' => 'Rentas.com',
                                        'Facebook' => 'Facebook',
                                        'Instagram' => 'Instagram',
                                        'Ticktok' => 'Ticktok',
                                        'Recomendado' => 'Recomendado',
                                        'Evento' => 'Evento',
                                        'Otro' => 'Otro',
                                    ])
                                    ->native(false)
                                    ->columnSpanFull(),

                                Forms\Components\Select::make('calificacion_lead')
                                    ->label('Calificación')
                                    ->options([
                                        'perfilado' => 'Perfilado',
                                        'potencial' => 'Potencial',
                                        'seguimiento' => 'Seguimiento',
                                        'falso_lead' => 'Falso lead',
                                        'sin_presupuesto' => 'Sin presupuesto',
                                        'no_interesado' => 'No interesado',
                                        'mistery_shopper' => 'Mistery Shopper',
                                    ])->columnSpanFull(),
                            ]),
                    ]),

                    // COLUMNA DERECHA (Pestañas estilo Nocnok)
                    Forms\Components\Group::make()->columnSpan(1)->schema([

                        Forms\Components\Tabs::make('CRM Tabs')
                            ->tabs([

                                // --- NOTAS Y ACCIONES ---
                                Forms\Components\Tabs\Tab::make('Notas y Seguimiento')
                                    ->icon('heroicon-m-document-text')
                                    ->schema([

                                        // Botonera de acciones
                                        Forms\Components\Actions::make([

                                            Forms\Components\Actions\Action::make('agregar_nota')
                                                ->label('Agregar Nota')
                                                ->icon('heroicon-m-pencil-square')
                                                ->color('warning')
                                                ->form([
                                                    Forms\Components\Textarea::make('nota')
                                                        ->label('Escribe aquí tu nota')
                                                        ->required()
                                                        ->rows(3),
                                                ])
                                                ->action(function (array $data, ?Lead $record) {
                                                    if ($record) {
                                                        $hist = $record->historial_acciones ?? [];
                                                        $hist[] = ['fecha' => now()->format('d/m/Y H:i'), 'accion' => 'Nota: '.$data['nota']];
                                                        $record->update(['historial_acciones' => $hist]);
                                                    }
                                                })->visible(fn (?Lead $record) => $record !== null),

                                            Forms\Components\Actions\Action::make('crear_cita')
                                                ->label('Cita')
                                                ->icon('heroicon-m-calendar')
                                                ->color('primary')
                                                ->form([
                                                    Forms\Components\DatePicker::make('fecha')->required(),
                                                    Forms\Components\TimePicker::make('hora')->required(),
                                                    Forms\Components\Textarea::make('observaciones'),
                                                ])
                                                ->action(function (array $data, ?Lead $record) {
                                                    if ($record) {
                                                        $hist = $record->historial_acciones ?? [];
                                                        $hist[] = ['fecha' => now()->format('d/m/Y H:i'), 'accion' => "Cita: {$data['fecha']} a las {$data['hora']} - ".($data['observaciones'] ?? '')];
                                                        $record->update(['etapa' => 'cita', 'historial_acciones' => $hist]);
                                                    }
                                                })->visible(fn (?Lead $record) => $record !== null),

                                            Forms\Components\Actions\Action::make('registrar_llamada')
                                                ->label('Llamada')
                                                ->icon('heroicon-m-phone')
                                                ->color('gray')
                                                ->requiresConfirmation()
                                                ->action(function (?Lead $record) {
                                                    if ($record) {
                                                        $hist = $record->historial_acciones ?? [];
                                                        $hist[] = ['fecha' => now()->format('d/m/Y H:i'), 'accion' => 'Llamada telefónica realizada'];
                                                        $record->update(['etapa' => 'contactado', 'historial_acciones' => $hist]);
                                                    }
                                                })->visible(fn (?Lead $record) => $record !== null),
                                        ]),

                                        // Muro de historial
                                        Forms\Components\ViewField::make('historial_acciones')
                                            ->view('filament.forms.components.lead-history')
                                            ->label('')
                                            ->visible(fn (?Lead $record) => $record !== null && ! empty($record->historial_acciones)),
                                    ]),

                                // --- PROPIEDADES DE INTERÉS ---
                                Forms\Components\Tabs\Tab::make('Propiedades de interés')
                                    ->icon('heroicon-m-home-modern')
                                    ->schema([
                                        Forms\Components\TextInput::make('url_propiedad')
                                            ->label('URL de la propiedad / Nocnok ID')
                                            ->suffixAction(
                                                Forms\Components\Actions\Action::make('visitar')
                                                    ->icon('heroicon-m-arrow-top-right-on-square')
                                                    ->url(fn ($state) => $state, shouldOpenInNewTab: true)
                                            ),

                                        Forms\Components\Grid::make(2)->schema([
                                            Forms\Components\TextInput::make('metros_cuadrados')
                                                ->label('Metros Cuadrados')
                                                ->numeric(),
                                            Forms\Components\TextInput::make('numero_recamaras')
                                                ->label('Nº de Recámaras')
                                                ->numeric(),
                                        ]),

                                        Forms\Components\TextInput::make('localidades')
                                            ->label('Zonas o Localidades de interés'),

                                        Forms\Components\Textarea::make('mensaje')
                                            ->label('Mensaje de solicitud original')
                                            ->disabled()
                                            ->rows(3),
                                    ]),

                                // --- MENSAJES / WHATSAPP ---
                                Forms\Components\Tabs\Tab::make('WhatsApp')
                                    ->icon('heroicon-m-chat-bubble-bottom-center-text')
                                    ->schema([
                                        Forms\Components\Actions::make([
                                            Forms\Components\Actions\Action::make('send_whatsapp_from_tab')
                                                ->label('Enviar WhatsApp')
                                                ->icon('heroicon-m-paper-airplane')
                                                ->color('success')
                                                ->visible(fn (?Lead $record) => $record !== null && filled($record->normalizedWhatsappForEvolution()))
                                                ->form([
                                                    Forms\Components\Select::make('instance_id')
                                                        ->label('Instancia conectada')
                                                        ->options(function (): array {
                                                            return WhatsappInstance::query()
                                                                ->where('status', StatusConnectionEnum::OPEN)
                                                                ->orderBy('name')
                                                                ->pluck('name', 'id')
                                                                ->all();
                                                        })
                                                        ->default(fn () => auth()->user()?->evolution_whatsapp_instance_id)
                                                        ->required()
                                                        ->searchable(),
                                                    Forms\Components\Select::make('type')
                                                        ->label('Tipo de mensaje')
                                                        ->options([
                                                            'text' => 'Texto',
                                                            'image' => 'Imagen',
                                                            'document' => 'Documento',
                                                        ])
                                                        ->default('text')
                                                        ->live()
                                                        ->required(),
                                                    Forms\Components\Textarea::make('message')
                                                        ->label('Mensaje')
                                                        ->rows(4)
                                                        ->required(fn (Forms\Get $get): bool => $get('type') === 'text')
                                                        ->visible(fn (Forms\Get $get): bool => $get('type') === 'text'),
                                                    Forms\Components\FileUpload::make('media')
                                                        ->label('Archivo')
                                                        ->disk('public')
                                                        ->directory('whatsapp-media')
                                                        ->acceptedFileTypes([
                                                            'image/jpeg',
                                                            'image/png',
                                                            'image/webp',
                                                            'application/pdf',
                                                            'application/msword',
                                                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                                        ])
                                                        ->maxSize(16384)
                                                        ->visible(fn (Forms\Get $get): bool => in_array($get('type'), ['image', 'document'], true))
                                                        ->required(fn (Forms\Get $get): bool => in_array($get('type'), ['image', 'document'], true)),
                                                    Forms\Components\TextInput::make('caption')
                                                        ->label('Descripción')
                                                        ->maxLength(255)
                                                        ->visible(fn (Forms\Get $get): bool => in_array($get('type'), ['image', 'document'], true)),
                                                ])
                                                ->action(function (array $data, ?Lead $record): void {
                                                    if (! $record) {
                                                        return;
                                                    }

                                                    $number = $record->normalizedWhatsappForEvolution();
                                                    if (! $number) {
                                                        Notification::make()->danger()->title('Teléfono no válido')->send();

                                                        return;
                                                    }

                                                    try {
                                                        $service = app(WhatsappService::class);
                                                        $instance = static::resolveWhatsappInstance($data['instance_id'] ?? null);
                                                        if (! $instance) {
                                                            Notification::make()->danger()->title('Instancia inválida')->body('Selecciona una instancia conectada válida.')->send();

                                                            return;
                                                        }
                                                        $type = (string) ($data['type'] ?? 'text');
                                                        $caption = $data['caption'] ?? null;

                                                        if ($type === 'image') {
                                                            $service->sendImage($instance->id, $number, (string) $data['media'], $caption, 'public');
                                                        } elseif ($type === 'document') {
                                                            $service->sendDocument($instance->id, $number, (string) $data['media'], basename((string) $data['media']), $caption, 'public');
                                                        } else {
                                                            $service->sendText($instance->id, $number, (string) $data['message']);
                                                        }

                                                        $bodyText = $data['message'] ?? $caption ?? basename((string) ($data['media'] ?? ''));
                                                        WhatsappMessage::create([
                                                            'wa_message_id' => 'local-'.uniqid(),
                                                            'phone'         => $number,
                                                            'direction'     => 'out',
                                                            'body'          => $bodyText,
                                                            'lead_id'       => $record->id,
                                                            'user_id'       => auth()->id(),
                                                            'sent_at'       => now(),
                                                        ]);

                                                        Notification::make()
                                                            ->success()
                                                            ->title('Mensaje enviado')
                                                            ->body('El mensaje se agregó al historial del chat.')
                                                            ->send();
                                                    } catch (\Throwable $e) {
                                                        Notification::make()
                                                            ->danger()
                                                            ->title('Error al enviar')
                                                            ->body(static::friendlyWhatsappError($e->getMessage()))
                                                            ->send();
                                                    }
                                                }),
                                        ]),
                                        Forms\Components\ViewField::make('whatsapp_chat')
                                            ->view('filament.forms.components.lead-whatsapp-chat')
                                            ->label(''),
                                    ]),
                            ])
                            ->columnSpanFull(),
                    ]),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort(fn ($query) => $query->orderByRaw("CASE WHEN etapa = 'no_contactado' THEN 1 ELSE 2 END")->orderBy('created_at', 'desc'))
            ->modifyQueryUsing(fn (Builder $query) => $query->whereNotIn('etapa', ['ganado', 'perdido', 'no_califica']))
            ->headerActions([
                Action::make('exportar_todo_bonito')
                    ->label('Descargar Reporte Oficial')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('primary')
                    ->action(function () {
                        return Excel::download(new LeadsExport(Lead::all()), 'Reporte_Interesados_'.date('Y-m-d').'.xlsx');
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

                // Agregado a la tabla para mayor visibilidad
                Tables\Columns\TextColumn::make('calificacion_lead')
                    ->label('Calificación')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state))),

                Tables\Columns\TextColumn::make('canal')
                    ->label('Canal')
                    ->badge()
                    ->color('gray')
                    ->placeholder('—')
                    ->formatStateUsing(function ($state): ?string {
                        if ($state instanceof LeadCanal) {
                            return $state->getLabel();
                        }

                        return is_string($state) && $state !== ''
                            ? LeadCanal::tryFrom($state)?->getLabel()
                            : null;
                    }),

                Tables\Columns\TextColumn::make('origen')
                    ->label('Origen')
                    ->badge()
                    ->color('primary'),

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
                Action::make('contactar')
                    ->label('Ya contacté')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(fn (Lead $record) => $record->update(['etapa' => 'contactado']))
                    ->visible(fn (Lead $record) => $record->etapa === 'no_contactado'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    BulkAction::make('exportar_seleccion_bonito')
                        ->label('Exportar Selección con Logo')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function (Collection $records) {
                            return Excel::download(new LeadsExport($records), 'Seleccion_Interesados_'.date('Y-m-d').'.xlsx');
                        })
                        ->deselectRecordsAfterCompletion(),
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

    protected static function friendlyWhatsappError(string $message): string
    {
        $msg = strtolower($message);

        if (str_contains($msg, 'exists') || str_contains($msg, 'not exists') || str_contains($msg, 'bad request')) {
            return 'El número no existe en WhatsApp o tiene un formato inválido. Verifica lada y país.';
        }

        if (str_contains($msg, 'instance') && str_contains($msg, 'not found')) {
            return 'La instancia seleccionada no existe en Evolution. Revisa en Admin > WhatsApp > Instancias.';
        }

        if (str_contains($msg, 'connection') || str_contains($msg, 'close')) {
            return 'La instancia no está conectada. Abre el QR y confirma estado en línea.';
        }

        return $message;
    }

    protected static function resolveWhatsappInstance(mixed $instanceInput): ?WhatsappInstance
    {
        if (blank($instanceInput)) {
            return null;
        }

        $instance = WhatsappInstance::query()->find($instanceInput);
        if ($instance) {
            return $instance;
        }

        return WhatsappInstance::query()
            ->where('name', (string) $instanceInput)
            ->first();
    }
}
