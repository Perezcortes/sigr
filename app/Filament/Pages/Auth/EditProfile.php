<?php

namespace App\Filament\Pages\Auth;

use App\Models\Estate;
use App\Models\Municipality;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Support\Enums\MaxWidth;

class EditProfile extends \Filament\Pages\Auth\EditProfile
{
    protected ?string $maxWidth = MaxWidth::SevenExtraLarge->value;

    protected function isAsesor(): bool
    {
        $user = $this->getUser();

        return $user->hasRole('Asesor') || $user->roles()->where('id', 3)->exists();
    }

    protected function getPhoneFormComponent(): Component
    {
        return TextInput::make('telefono')
            ->label('Teléfono')
            ->tel()
            ->maxLength(20)
            ->visible(fn (): bool => $this->isAsesor());
    }

    protected function getAvatarFormComponent(): Component
    {
        return \Filament\Forms\Components\SpatieMediaLibraryFileUpload::make('avatar')
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
            ->afterStateUpdated(function (\Filament\Forms\Set $set): void {
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

    public function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form
            ->schema([
                $this->getNameFormComponent(),
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
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
            ]);
    }
}