<?php

namespace App\Filament\Resources;

use App\Enums\LeadCanal;
use App\Exports\LeadsExport;
use App\Filament\Resources\LeadResource\Pages;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\WhatsappInstance;
use App\Models\WhatsappMessage;
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
use Illuminate\Support\HtmlString;
use Maatwebsite\Excel\Facades\Excel;
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

                                // --- PROPIEDAD ---
                                Forms\Components\Tabs\Tab::make('Propiedad')
                                    ->icon('heroicon-m-home-modern')
                                    ->schema([
                                        Forms\Components\TextInput::make('url_propiedad')
                                            ->label('URL de la propiedad / Nocnok ID')
                                            ->suffixAction(
                                                Forms\Components\Actions\Action::make('visitar')
                                                    ->icon('heroicon-m-arrow-top-right-on-square')
                                                    ->url(fn ($state) => $state, shouldOpenInNewTab: true)
                                            ),

                                        Forms\Components\Placeholder::make('imagen_propiedad_vista')
                                            ->label('Imagen de la propiedad')
                                            ->content(function (?Lead $record): HtmlString|string {
                                                if (! filled($record?->imagen_propiedad)) {
                                                    return 'Sin imagen';
                                                }

                                                return new HtmlString(
                                                    '<img src="'.e($record->imagen_propiedad).'" alt="Propiedad" class="rounded-lg max-h-48 object-cover" />'
                                                );
                                            })
                                            ->visible(fn (?Lead $record): bool => filled($record?->imagen_propiedad)),

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

                                // --- SEGUIMIENTO ---
                                Forms\Components\Tabs\Tab::make('Seguimiento')
                                    ->icon('heroicon-o-calendar-days')
                                    ->visible(fn ($livewire) => $livewire instanceof Pages\EditLead)
                                    ->schema(static::seguimientoTabSchema()),

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
                                                            'phone' => $number,
                                                            'direction' => 'out',
                                                            'body' => $bodyText,
                                                            'lead_id' => $record->id,
                                                            'user_id' => auth()->id(),
                                                            'sent_at' => now(),
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

                                // --- NOTAS Y ACCIONES ---
                                Forms\Components\Tabs\Tab::make('Notas')
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
                            ])
                            ->columnSpanFull(),
                    ]),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->actionsColumnLabel('Acciones')
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
                    ->label('Etapa')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'no_contactado' => 'danger',
                        'ganado' => 'success',
                        'perdido', 'no_califica' => 'gray',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'no_contactado' => 'No contactado',
                        'contactado' => 'Contactado',
                        'cita' => 'Cita',
                        'seguimiento' => 'Seguimiento',
                        'propuesta' => 'Propuesta',
                        'en_cierre' => 'En cierre',
                        'en_proceso' => 'En proceso',
                        'nuevo' => 'Nuevo',
                        'ganado' => 'Ganado',
                        'perdido' => 'Perdido',
                        'no_califica' => 'No califica',
                        default => $state ? ucfirst(str_replace('_', ' ', $state)) : '—',
                    }),

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

    /**
     * @return array<int, Forms\Components\Component>
     */
    protected static function seguimientoTabSchema(): array
    {
        return [
            Forms\Components\Placeholder::make('seguimiento_descripcion')
                ->label('')
                ->content('Agenda actividades y próximas acciones con este interesado.'),

            Forms\Components\Hidden::make('seguimiento_show_form')
                ->default(false)
                ->dehydrated(false),

            Forms\Components\Actions::make([
                Forms\Components\Actions\Action::make('agregar_actividad')
                    ->label('Agregar actividad')
                    ->icon('heroicon-m-plus')
                    ->color('primary')
                    ->visible(fn (Forms\Get $get): bool => ! (bool) $get('seguimiento_show_form'))
                    ->action(function (Forms\Set $set): void {
                        $set('seguimiento_show_form', true);
                        $set('seguimiento_draft', static::seguimientoDraftDefaults());
                    }),
            ]),

            Forms\Components\Group::make()
                ->visible(fn (Forms\Get $get): bool => (bool) $get('seguimiento_show_form'))
                ->schema(static::seguimientoActivityFieldSchema()),

            Forms\Components\Actions::make([
                Forms\Components\Actions\Action::make('guardar_actividad')
                    ->label('Guardar')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->visible(fn (Forms\Get $get): bool => (bool) $get('seguimiento_show_form'))
                    ->action(function (Forms\Get $get, Forms\Set $set, Lead $record, Pages\EditLead $livewire): void {
                        $draft = static::resolveSeguimientoDraftFromForm($get, $livewire);

                        if (blank($draft['fecha'] ?? null) || blank(trim((string) ($draft['descripcion'] ?? '')))) {
                            Notification::make()
                                ->danger()
                                ->title('Completa la fecha y la descripción')
                                ->send();

                            return;
                        }

                        $record->activities()->create([
                            'user_id' => auth()->id(),
                            'fecha' => $draft['fecha'],
                            'hora' => $draft['hora'] ?? '09:00',
                            'descripcion' => trim((string) $draft['descripcion']),
                            'completada' => (bool) ($draft['completada'] ?? false),
                        ]);

                        $set('seguimiento_show_form', false);
                        $set('seguimiento_draft', static::seguimientoDraftDefaults());
                        $livewire->refreshSeguimientoLista();

                        Notification::make()
                            ->success()
                            ->title('Actividad guardada')
                            ->send();
                    }),
                Forms\Components\Actions\Action::make('cancelar_actividad')
                    ->label('Cancelar')
                    ->icon('heroicon-m-x-mark')
                    ->color('gray')
                    ->visible(fn (Forms\Get $get): bool => (bool) $get('seguimiento_show_form'))
                    ->action(function (Forms\Set $set): void {
                        $set('seguimiento_show_form', false);
                        $set('seguimiento_draft', static::seguimientoDraftDefaults());
                    }),
            ]),

            Forms\Components\ViewField::make('seguimiento_lista')
                ->label('Actividades')
                ->view('filament.forms.components.lead-activities-list')
                ->viewData(fn (Pages\EditLead $livewire): array => [
                    'listKey' => $livewire->seguimientoListKey,
                ])
                ->visible(fn (Pages\EditLead $livewire): bool => $livewire->record->activities()->exists())
                ->key(fn (Pages\EditLead $livewire): string => 'seguimiento-list-'.$livewire->seguimientoListKey),
        ];
    }

    /**
     * @return array<int, Forms\Components\Component>
     */
    /**
     * @return array<int, Forms\Components\Component>
     */
    public static function seguimientoActivityEditFormSchema(): array
    {
        return [
            Forms\Components\Grid::make(3)->schema([
                Forms\Components\DatePicker::make('fecha')
                    ->label('Fecha')
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->defaultFocusedDate(now()),

                Forms\Components\TextInput::make('hora')
                    ->label('Hora')
                    ->type('time')
                    ->required(),

                Forms\Components\Toggle::make('completada')
                    ->label('Realizada')
                    ->inline(false),
            ]),

            Forms\Components\Textarea::make('descripcion')
                ->label('¿Qué vas a hacer con este prospecto?')
                ->required()
                ->rows(3)
                ->columnSpanFull(),
        ];
    }

    protected static function seguimientoActivityFieldSchema(): array
    {
        return [
            Forms\Components\Grid::make(3)->schema([
                Forms\Components\DatePicker::make('seguimiento_draft.fecha')
                    ->label('Fecha')
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->default(now())
                    ->defaultFocusedDate(now())
                    ->live(),

                Forms\Components\TextInput::make('seguimiento_draft.hora')
                    ->label('Hora')
                    ->type('time')
                    ->required()
                    ->default('09:00')
                    ->live(),

                Forms\Components\Toggle::make('seguimiento_draft.completada')
                    ->label('Realizada')
                    ->inline(false)
                    ->default(false),
            ]),

            Forms\Components\Textarea::make('seguimiento_draft.descripcion')
                ->label('¿Qué vas a hacer con este prospecto?')
                ->required()
                ->rows(2)
                ->placeholder('Ej: Llamarle para confirmar visita, enviar cotización, agendar cita...')
                ->columnSpanFull()
                ->live(),
        ];
    }

    /**
     * @return array{fecha?: mixed, hora?: mixed, descripcion?: mixed, completada?: mixed}
     */
    protected static function resolveSeguimientoDraftFromForm(Forms\Get $get, Pages\EditLead $livewire): array
    {
        $draft = $get('seguimiento_draft');

        if (is_array($draft) && filled($draft['fecha'] ?? null) && filled($draft['descripcion'] ?? null)) {
            return $draft;
        }

        return data_get($livewire->form->getRawState(), 'seguimiento_draft', [])
            ?: data_get($livewire->data, 'seguimiento_draft', []);
    }

    /**
     * @return array{fecha: string, hora: string, descripcion: string, completada: bool}
     */
    public static function seguimientoDraftDefaults(): array
    {
        return [
            'fecha' => now()->format('Y-m-d'),
            'hora' => '09:00',
            'descripcion' => '',
            'completada' => false,
        ];
    }

    /**
     * Pendientes primero (fecha más próxima arriba), luego realizadas (más recientes primero).
     *
     * @return \Illuminate\Support\Collection<int, LeadActivity>
     */
    public static function orderedActivitiesForList(Lead $lead): \Illuminate\Support\Collection
    {
        $activities = $lead->activities()->with('user')->get();

        $pendientes = $activities
            ->where('completada', false)
            ->sortBy(fn (LeadActivity $activity): string => $activity->fecha->format('Y-m-d').' '.($activity->hora ?? '00:00'));

        $realizadas = $activities
            ->where('completada', true)
            ->sortByDesc(fn (LeadActivity $activity): string => $activity->fecha->format('Y-m-d').' '.($activity->hora ?? '00:00'));

        return $pendientes->concat($realizadas)->values();
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
