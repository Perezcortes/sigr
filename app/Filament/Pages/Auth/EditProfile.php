<?php

namespace App\Filament\Pages\Auth;

use App\Models\Estate;
use App\Models\Municipality;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Str;

class EditProfile extends \Filament\Pages\Auth\EditProfile
{
    protected ?string $maxWidth = MaxWidth::SevenExtraLarge->value;

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
