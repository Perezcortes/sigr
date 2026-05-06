<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\User;
use App\Models\WhatsappInstance;
use App\Services\EvolutionInstanceBootstrapper;
use Filament\Notifications\Notification;
use Livewire\Attributes\On;
use Livewire\Component;
use WallaceMartinss\FilamentEvolution\Enums\StatusConnectionEnum;

class AdvisorWhatsappEvolutionPanel extends Component
{
    public string $createNumber = '';

    public bool $showQr = false;

    public ?WhatsappInstance $whatsappInstance = null;

    public function mount(): void
    {
        if (! $this->userIsAdvisor()) {
            return;
        }

        $this->loadAdvisorState();
    }

    protected function userIsAdvisor(): bool
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        return $user->hasRole('Agente');
    }

    public function loadAdvisorState(): void
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return;
        }

        $user->loadMissing('evolutionWhatsappInstance');
        $this->createNumber = (string) ($user->whatsapp ?? '');
        $this->whatsappInstance = $user->evolutionWhatsappInstance;
    }

    public function createInstance(): void
    {
        if (! $this->userIsAdvisor()) {
            return;
        }

        $user = auth()->user();

        if (! $user instanceof User) {
            return;
        }

        $user->refresh();

        if (filled($user->evolution_whatsapp_instance_id)) {
            Notification::make()
                ->warning()
                ->title('Instancia ya asignada')
                ->body('Tu cuenta ya tiene una instancia de WhatsApp. Usa «Mostrar código QR» para conectar.')
                ->send();
            $this->loadAdvisorState();

            return;
        }

        $this->validate([
            'createNumber' => ['required', 'string', 'max:20'],
        ], attributes: [
            'createNumber' => 'número WhatsApp',
        ]);

        $this->whatsappInstance = app(EvolutionInstanceBootstrapper::class)
            ->createAdvisorInstance($user, $this->createNumber);

        $this->showQr = true;

        Notification::make()
            ->success()
            ->title('Instancia creada')
            ->body('Escanea el código QR para vincular tu WhatsApp.')
            ->send();
    }

    public function openQr(): void
    {
        if (! $this->userIsAdvisor()) {
            return;
        }

        $this->loadAdvisorState();

        if ($this->whatsappInstance === null) {
            Notification::make()
                ->warning()
                ->title('Sin instancia')
                ->body('Primero crea una instancia con tu número.')
                ->send();

            return;
        }

        if ($this->whatsappInstance->status === StatusConnectionEnum::OPEN) {
            Notification::make()
                ->info()
                ->title('Ya conectado')
                ->body('Tu WhatsApp ya está conectado.')
                ->send();

            return;
        }

        $this->showQr = true;
    }

    public function closeQr(): void
    {
        $this->showQr = false;
        $this->loadAdvisorState();
    }

    #[On('instance-connected')]
    public function onInstanceConnected(): void
    {
        $this->showQr = false;
        $this->loadAdvisorState();

        Notification::make()
            ->success()
            ->title('WhatsApp conectado')
            ->body('Tu línea quedó vinculada correctamente.')
            ->send();
    }

    public function render()
    {
        if (! $this->userIsAdvisor()) {
            return view('livewire.advisor-whatsapp-evolution-panel-empty');
        }

        return view('livewire.advisor-whatsapp-evolution-panel');
    }
}
