<?php

namespace App\Filament\Pages\Auth;

use App\Models\Estate;
use App\Models\Municipality;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class EditProfile extends \Filament\Pages\Auth\EditProfile
{
    protected ?string $maxWidth = MaxWidth::SevenExtraLarge->value;

    private const NOTIFICATION_FIELDS = [
        'notif_recordatorios_email',
        'notif_recordatorios_push',
        'notif_recordatorios_whatsapp',
        'notif_reporte_pago_email',
        'notif_reporte_pago_push',
        'notif_reporte_pago_whatsapp',
        'notif_mensajes_email',
        'notif_mensajes_push',
        'notif_mensajes_whatsapp',
        'notif_mantenimiento_email',
        'notif_mantenimiento_push',
        'notif_mantenimiento_whatsapp',
    ];

    public bool $isInitializingForm = true;

    public function mount(): void
    {
        parent::mount();

        $this->isInitializingForm = false;
    }

    // Autoguarda solo las 12 preferencias de notificación al tocar un toggle (mismo patrón
    // que tenía SettingsManager.php por-renta) — el resto del form sigue guardándose con
    // el botón "Guardar cambios" normal de save().
    public function updatedData(): void
    {
        if ($this->isInitializingForm) {
            return;
        }

        $notificationData = array_intersect_key($this->data, array_flip(self::NOTIFICATION_FIELDS));

        if (empty($notificationData)) {
            return;
        }

        $this->getUser()->update($notificationData);

        Notification::make()
            ->title('Preferencia actualizada')
            ->success()
            ->send();
    }

    protected function isAsesor(): bool
    {
        $user = $this->getUser();

        return $user->hasAnyRole(['Agente', 'Asesor']);
    }

    protected function getPhoneFormComponent(): Component
    {
        return TextInput::make('telefono')
            ->label('Teléfono')
            ->tel()
            ->maxLength(20)
            ->visible(fn (): bool => $this->isAsesor());
    }

    protected function getNameFormComponent(): Component
    {
        return TextInput::make('name')
            ->label(__('filament-panels::pages/auth/edit-profile.form.name.label'))
            ->required()
            ->maxLength(255)
            ->live(onBlur: true)
            ->afterStateUpdated(function (Set $set, Get $get, ?string $old, ?string $state): void {
                $currentSlug = (string) $get('slug');
                $oldGeneratedSlug = Str::slug((string) $old);

                if (filled($currentSlug) && $currentSlug !== $oldGeneratedSlug) {
                    return;
                }

                $set('slug', Str::slug((string) $state));
            });
    }

    protected function getSlugFormComponent(): Component
    {
        return TextInput::make('slug')
            ->label('Slug')
            ->required()
            ->maxLength(255)
            ->alphaDash()
            ->unique(table: 'users', column: 'slug', ignorable: fn () => $this->getUser())
            ->helperText('Se autocompleta con el nombre, pero puedes editarlo.')
            ->visible(fn (): bool => $this->isAsesor());
    }

    protected function getAvatarFormComponent(): Component
    {
        return SpatieMediaLibraryFileUpload::make('avatar')
            ->label('Foto de perfil')
            ->collection('profile-images')
            ->avatar()
            ->alignCenter()
            ->columnSpanFull();
    }

    protected function getWhatsappFormComponent(): Component
    {
        return TextInput::make('whatsapp')
            ->label('WhatsApp')
            ->tel()
            ->maxLength(20)
            ->visible(fn (): bool => $this->isAsesor());
    }

    protected function getFacebookFormComponent(): Component
    {
        return TextInput::make('facebook')
            ->label('Facebook')
            ->maxLength(255)
            ->url()
            ->prefixIcon('heroicon-o-link')
            ->visible(fn (): bool => $this->isAsesor());
    }

    protected function getInstagramFormComponent(): Component
    {
        return TextInput::make('instagram')
            ->label('Instagram')
            ->maxLength(255)
            ->url()
            ->prefixIcon('heroicon-o-link')
            ->visible(fn (): bool => $this->isAsesor());
    }

    protected function getLinkedinFormComponent(): Component
    {
        return TextInput::make('linkedin')
            ->label('LinkedIn')
            ->maxLength(255)
            ->url()
            ->prefixIcon('heroicon-o-link')
            ->visible(fn (): bool => $this->isAsesor());
    }

    protected function getAboutMeFormComponent(): Component
    {
        return RichEditor::make('about_me')
            ->label('Sobre mí')
            ->toolbarButtons([
                'bold',
                'italic',
                'underline',
                'strike',
                'h2',
                'h3',
                'blockquote',
                'bulletList',
                'orderedList',
                'redo',
                'undo',
            ])
            ->columnSpanFull()
            ->visible(fn (): bool => $this->isAsesor());
    }

    // Mismo diseño (Section/Grid/Placeholder/Toggle) que tenía SettingsManager.php por-renta,
    // ahora sobre el usuario: una sola preferencia por cuenta, sin importar cuántas rentas tenga.
    protected function getNotificacionesFormComponent(): Component
    {
        return Section::make('Notificaciones')
            ->extraAttributes(['class' => 'shadow-sm border-gray-100'])
            ->columnSpanFull()
            ->schema([
                Grid::make(4)
                    ->extraAttributes([
                        // Filament fuerza `inline-flex` en el switch; con eso `mx-auto` no centra. Forzamos flex en bloque + márgenes.
                        'class' => '[&_button.fi-fo-toggle]:!flex [&_button.fi-fo-toggle]:!mx-auto',
                    ])
                    ->schema([
                        Placeholder::make('notif_header_tipo')
                            ->hiddenLabel()
                            ->extraAttributes(['class' => 'flex min-h-6 items-center'])
                            ->content(new HtmlString('<span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Tipo de notificación</span>')),
                        Placeholder::make('notif_header_email')
                            ->hiddenLabel()
                            ->extraAttributes(['class' => 'flex min-h-6 items-center justify-center text-center'])
                            ->content(new HtmlString('<span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Email</span>')),
                        Placeholder::make('notif_header_push')
                            ->hiddenLabel()
                            ->extraAttributes(['class' => 'flex min-h-6 items-center justify-center text-center'])
                            ->content(new HtmlString('<span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Push</span>')),
                        Placeholder::make('notif_header_whatsapp')
                            ->hiddenLabel()
                            ->extraAttributes(['class' => 'flex min-h-6 items-center justify-center text-center'])
                            ->content(new HtmlString('<span class="text-xs font-semibold uppercase tracking-wide text-gray-500">WhatsApp</span>')),

                        Placeholder::make('notif_row_recordatorios')
                            ->hiddenLabel()
                            ->extraAttributes(['class' => 'flex min-h-11 items-center'])
                            ->content(new HtmlString('<span class="text-sm font-medium text-gray-800 dark:text-gray-100">Recordatorios de pago</span>')),
                        Toggle::make('notif_recordatorios_email')->hiddenLabel()->default(true)->onColor('warning')->live(),
                        Toggle::make('notif_recordatorios_push')->hiddenLabel()->default(true)->onColor('warning')->live(),
                        Toggle::make('notif_recordatorios_whatsapp')->hiddenLabel()->default(true)->onColor('warning')->live(),

                        Placeholder::make('notif_row_reporte_pago')
                            ->hiddenLabel()
                            ->extraAttributes(['class' => 'flex min-h-11 items-center'])
                            ->content(new HtmlString('<span class="text-sm font-medium text-gray-800 dark:text-gray-100">Reporte de pago</span>')),
                        Toggle::make('notif_reporte_pago_email')->hiddenLabel()->default(true)->onColor('warning')->live(),
                        Toggle::make('notif_reporte_pago_push')->hiddenLabel()->default(true)->onColor('warning')->live(),
                        Toggle::make('notif_reporte_pago_whatsapp')->hiddenLabel()->default(true)->onColor('warning')->live(),

                        Placeholder::make('notif_row_mensajes')
                            ->hiddenLabel()
                            ->extraAttributes(['class' => 'flex min-h-11 items-center'])
                            ->content(new HtmlString('<span class="text-sm font-medium text-gray-800 dark:text-gray-100">Mensajes</span>')),
                        Toggle::make('notif_mensajes_email')->hiddenLabel()->default(true)->onColor('warning')->live(),
                        Toggle::make('notif_mensajes_push')->hiddenLabel()->default(true)->onColor('warning')->live(),
                        Toggle::make('notif_mensajes_whatsapp')->hiddenLabel()->default(true)->onColor('warning')->live(),

                        Placeholder::make('notif_row_mantenimiento')
                            ->hiddenLabel()
                            ->extraAttributes(['class' => 'flex min-h-11 items-center'])
                            ->content(new HtmlString('<span class="text-sm font-medium text-gray-800 dark:text-gray-100">Reporte de mantenimiento</span>')),
                        Toggle::make('notif_mantenimiento_email')->hiddenLabel()->default(true)->onColor('warning')->live(),
                        Toggle::make('notif_mantenimiento_push')->hiddenLabel()->default(true)->onColor('warning')->live(),
                        Toggle::make('notif_mantenimiento_whatsapp')->hiddenLabel()->default(true)->onColor('warning')->live(),
                    ]),
            ]);
    }

    protected function getIdNocnokFormComponent(): Component
    {
        return TextInput::make('id_nocnok')
            ->label('ID Nocnok')
            ->maxLength(255)
            ->columnSpanFull();
    }

    protected function getZoneEstateFormComponent(): Component
    {
        return Select::make('zone_estate_id')
            ->label('Estado de zona')
            ->options(fn () => Estate::query()->orderBy('nombre')->pluck('nombre', 'id'))
            ->searchable()
            ->preload()
            ->live()
            ->afterStateUpdated(function (Set $set): void {
                $set('zone_city_ids', []);
            })
            ->visible(fn (): bool => $this->isAsesor());
    }

    protected function getZoneCitiesFormComponent(): Component
    {
        return Select::make('zone_city_ids')
            ->label('Municipios de zona')
            ->multiple()
            ->options(function (Get $get) {
                $estateId = $get('zone_estate_id');

                if (blank($estateId)) {
                    return [];
                }

                return Municipality::query()
                    ->where('state_id', $estateId)
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->toArray();
            })
            ->searchable()
            ->preload()
            ->visible(fn (): bool => $this->isAsesor());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getNameFormComponent(),
                $this->getSlugFormComponent(),
                $this->getEmailFormComponent(),
                $this->getAvatarFormComponent(),
                $this->getPhoneFormComponent(),
                $this->getWhatsappFormComponent(),
                $this->getFacebookFormComponent(),
                $this->getInstagramFormComponent(),
                $this->getLinkedinFormComponent(),
                $this->getAboutMeFormComponent(),
                $this->getIdNocnokFormComponent(),
                $this->getZoneEstateFormComponent(),
                $this->getZoneCitiesFormComponent(),
                $this->getNotificacionesFormComponent(),
                Section::make('WhatsApp Evolution')
                    ->description('Crea tu instancia y escanea el código QR para vincular tu línea con el panel (leads, envíos, etc.).')
                    ->visible(fn (): bool => $this->isAsesor())
                    ->schema([
                        ViewField::make('advisor_whatsapp_evolution')
                            ->view('filament.forms.components.advisor-whatsapp-evolution-panel')
                            ->label('')
                            ->dehydrated(false),
                    ])
                    ->columnSpanFull(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
            ]);
    }
}
