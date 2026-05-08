<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WhatsappInstanceResource\Pages;
use App\Models\WhatsappInstance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use WallaceMartinss\FilamentEvolution\Enums\StatusConnectionEnum;

class WhatsappInstanceResource extends Resource
{
    public const MANAGE_INSTANCES_PERMISSION = 'Gestionar instancias WhatsApp';

    protected static ?string $model = WhatsappInstance::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'WhatsApp';

    protected static ?int $navigationSort = 5;

    public static function canManageWhatsappInstances(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $user->hasRole('Administrador')
            || $user->can(self::MANAGE_INSTANCES_PERMISSION);
    }

    public static function canViewAny(): bool
    {
        return static::canManageWhatsappInstances();
    }

    public static function canCreate(): bool
    {
        return static::canManageWhatsappInstances();
    }

    public static function canView(Model $record): bool
    {
        return static::canManageWhatsappInstances();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canManageWhatsappInstances();
    }

    public static function canDelete(Model $record): bool
    {
        return static::canManageWhatsappInstances();
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-evolution::resource.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('filament-evolution::resource.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament-evolution::resource.plural_model_label');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Instance')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make(__('filament-evolution::resource.sections.instance_info'))
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label(__('filament-evolution::resource.fields.name'))
                                            ->helperText(__('filament-evolution::resource.fields.name_helper'))
                                            ->required()
                                            ->maxLength(255)
                                            ->unique(ignoreRecord: true),
                                        Forms\Components\TextInput::make('number')
                                            ->label(__('filament-evolution::resource.fields.number'))
                                            ->helperText(__('filament-evolution::resource.fields.number_helper'))
                                            ->required()
                                            ->tel()
                                            ->maxLength(20),
                                    ]),
                            ]),
                        Forms\Components\Tabs\Tab::make(__('filament-evolution::resource.sections.settings'))
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\ToggleButtons::make('reject_call')
                                            ->label(__('filament-evolution::resource.fields.reject_call'))
                                            ->helperText(__('filament-evolution::resource.fields.reject_call_helper'))
                                            ->default(config('filament-evolution.instance.reject_call', false))
                                            ->boolean()
                                            ->live()
                                            ->inline(),
                                        Forms\Components\ToggleButtons::make('groups_ignore')
                                            ->label(__('filament-evolution::resource.fields.groups_ignore'))
                                            ->helperText(__('filament-evolution::resource.fields.groups_ignore_helper'))
                                            ->default(config('filament-evolution.instance.groups_ignore', false))
                                            ->boolean()
                                            ->inline(),
                                        Forms\Components\ToggleButtons::make('always_online')
                                            ->label(__('filament-evolution::resource.fields.always_online'))
                                            ->helperText(__('filament-evolution::resource.fields.always_online_helper'))
                                            ->default(config('filament-evolution.instance.always_online', false))
                                            ->boolean()
                                            ->inline(),
                                        Forms\Components\ToggleButtons::make('read_messages')
                                            ->label(__('filament-evolution::resource.fields.read_messages'))
                                            ->helperText(__('filament-evolution::resource.fields.read_messages_helper'))
                                            ->default(config('filament-evolution.instance.read_messages', false))
                                            ->boolean()
                                            ->inline(),
                                        Forms\Components\ToggleButtons::make('read_status')
                                            ->label(__('filament-evolution::resource.fields.read_status'))
                                            ->helperText(__('filament-evolution::resource.fields.read_status_helper'))
                                            ->default(config('filament-evolution.instance.read_status', false))
                                            ->boolean()
                                            ->inline(),
                                        Forms\Components\ToggleButtons::make('sync_full_history')
                                            ->label(__('filament-evolution::resource.fields.sync_full_history'))
                                            ->helperText(__('filament-evolution::resource.fields.sync_full_history_helper'))
                                            ->default(config('filament-evolution.instance.sync_full_history', false))
                                            ->boolean()
                                            ->inline(),
                                        Forms\Components\TextInput::make('msg_call')
                                            ->label(__('filament-evolution::resource.fields.msg_call'))
                                            ->helperText(__('filament-evolution::resource.fields.msg_call_helper'))
                                            ->hidden(fn (Forms\Get $get) => $get('reject_call') == false)
                                            ->maxLength(255)
                                            ->default(config('filament-evolution.instance.msg_call', ''))
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('profile_picture_url')
                    ->label('')
                    ->alignCenter()
                    ->circular()
                    ->defaultImageUrl(fn () => 'https://ui-avatars.com/api/?name=WA&color=7F9CF5&background=EBF4FF'),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('filament-evolution::resource.fields.name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('number')
                    ->label(__('filament-evolution::resource.fields.number'))
                    ->alignCenter()
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('filament-evolution::resource.fields.status'))
                    ->badge()
                    ->alignCenter()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('filament-evolution::resource.fields.created_at'))
                    ->alignCenter()
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('filament-evolution::resource.fields.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(StatusConnectionEnum::class),
            ])
            ->actions([
                Tables\Actions\Action::make('connect')
                    ->label(__('filament-evolution::resource.actions.connect'))
                    ->icon('heroicon-o-qr-code')
                    ->color('success')
                    ->url(fn (WhatsappInstance $record): string => static::getUrl('index', ['connectInstanceId' => (string) $record->id]))
                    ->visible(fn (WhatsappInstance $record): bool => $record->status !== StatusConnectionEnum::OPEN),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
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
            'index' => Pages\ListWhatsappInstances::route('/'),
            'create' => Pages\CreateWhatsappInstance::route('/create'),
            'view' => Pages\ViewWhatsappInstance::route('/{record}'),
            'edit' => Pages\EditWhatsappInstance::route('/{record}/edit'),
        ];
    }
}
