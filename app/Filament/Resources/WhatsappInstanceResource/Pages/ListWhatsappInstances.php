<?php

namespace App\Filament\Resources\WhatsappInstanceResource\Pages;

use App\Filament\Resources\WhatsappInstanceResource;
use App\Models\WhatsappInstance;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;

class ListWhatsappInstances extends ListRecords
{
    protected static string $resource = WhatsappInstanceResource::class;

    protected static string $view = 'filament.pages.list-whatsapp-instances';

    #[Url(except: '')]
    public ?string $connectInstanceId = null;

    public ?WhatsappInstance $connectInstance = null;

    public bool $showQrCodeModal = false;

    public function mount(): void
    {
        parent::mount();

        if ($this->connectInstanceId) {
            $this->openConnectModal($this->connectInstanceId);
        }
    }

    public function openConnectModal(string $instanceId): void
    {
        $this->connectInstance = WhatsappInstance::find($instanceId);
        $this->showQrCodeModal = true;
        $this->dispatch('open-modal', id: 'qr-code-modal');
    }

    public function closeConnectModal(): void
    {
        $this->showQrCodeModal = false;
        $this->connectInstance = null;
        $this->connectInstanceId = null;
    }

    #[On('instance-connected')]
    public function handleInstanceConnected(): void
    {
        $this->closeConnectModal();
        $this->dispatch('close-modal', id: 'qr-code-modal');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
