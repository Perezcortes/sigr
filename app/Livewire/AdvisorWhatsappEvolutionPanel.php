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
    public ?int $advisorUserId = null;

    public string $createNumber = '';

    public bool $showQr = false;

    public ?WhatsappInstance $whatsappInstance = null;

    public function mount(): void
    {
        if (! $this->shouldShowPanel()) {
            return;
        }

        $this->loadAdvisorState();
    }

    /**
     * Perfil: usuario logueado. Admin: usuario con id advisorUserId (tras autorización).
     */
    protected function targetUser(): ?User
    {
        if ($this->advisorUserId === null) {
            $user = auth()->user();

            return $user instanceof User ? $user : null;
        }

        if (! $this->actingUserCanManageAdvisors()) {
            return null;
        }

        $target = User::query()->find($this->advisorUserId);

        return $target instanceof User ? $target : null;
    }

    /**
     * Misma regla que editar usuarios en Filament (Administrador o permiso explícito).
     */
    protected function actingUserCanManageAdvisors(): bool
    {
        $actor = auth()->user();

        if (! $actor instanceof User) {
            return false;
        }

        return $actor->hasRole('Administrador') || $actor->can('Gestionar Usuarios');
    }

    protected function shouldShowPanel(): bool
    {
        $target = $this->targetUser();

        if (! $target instanceof User) {
            return false;
        }

        if (! $target->hasAnyRole(['Agente', 'Asesor'])) {
            return false;
        }

        if ($this->advisorUserId !== null) {
            return $this->actingUserCanManageAdvisors();
        }

        return $target->hasAnyRole(['Agente', 'Asesor']);
    }

    public function loadAdvisorState(): void
    {
        $user = $this->targetUser();

        if (! $user instanceof User) {
            return;
        }

        $user->loadMissing('evolutionWhatsappInstance');
        $this->createNumber = (string) ($user->whatsapp ?? '');
        $this->whatsappInstance = $user->evolutionWhatsappInstance;
    }

    public function createInstance(): void
    {
        if (! $this->shouldShowPanel()) {
            return;
        }

        $user = $this->targetUser();

        if (! $user instanceof User) {
            return;
        }

        $user->refresh();

        if (filled($user->evolution_whatsapp_instance_id)) {
            Notification::make()
                ->warning()
                ->title('Instancia ya asignada')
                ->body($this->advisorUserId !== null
                    ? 'Esta cuenta ya tiene una instancia de WhatsApp. Usa «Mostrar código QR» para conectar.'
                    : 'Tu cuenta ya tiene una instancia de WhatsApp. Usa «Mostrar código QR» para conectar.')
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
            ->body('Escanea el código QR para vincular el WhatsApp.')
            ->send();
    }

    public function openQr(): void
    {
        if (! $this->shouldShowPanel()) {
            return;
        }

        $this->loadAdvisorState();

        if ($this->whatsappInstance === null) {
            Notification::make()
                ->warning()
                ->title('Sin instancia')
                ->body('Primero crea una instancia con el número.')
                ->send();

            return;
        }

        if ($this->whatsappInstance->status === StatusConnectionEnum::OPEN) {
            Notification::make()
                ->info()
                ->title('Ya conectado')
                ->body('El WhatsApp ya está conectado.')
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
            ->body('La línea quedó vinculada correctamente.')
            ->send();
    }

    public function render()
    {
        if (! $this->shouldShowPanel()) {
            return view('livewire.advisor-whatsapp-evolution-panel-empty');
        }

        return view('livewire.advisor-whatsapp-evolution-panel');
    }
}
