<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\User;
use App\Models\WhatsappInstance;
use App\Services\EvolutionInstanceBootstrapper;
use App\Services\OpenWaService;
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

    public ?string $qrCode = null;

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

        if (! $target->hasAnyRole(['Agente', 'Asesor', 'Administrador'])) {
            return false;
        }

        if ($this->advisorUserId !== null) {
            return $this->actingUserCanManageAdvisors();
        }

        return $target->hasAnyRole(['Agente', 'Asesor', 'Administrador']);
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

        if ($this->whatsappInstance && $this->whatsappInstance->status !== StatusConnectionEnum::OPEN) {
            $this->checkStatus();
        }
    }

    public function checkStatus(): void
    {
        if (! $this->whatsappInstance) {
            return;
        }

        $openWa = app(OpenWaService::class);

        // Si es una instancia legacy sin instance_id, crearla y arrancarla automáticamente
        if (! $this->whatsappInstance->instance_id) {
            try {
                $session = $openWa->createSession($this->whatsappInstance->name);
                $uuid = $session['id'];

                $this->whatsappInstance->update([
                    'instance_id' => $uuid,
                    'status' => StatusConnectionEnum::CONNECTING,
                ]);

                $openWa->startSession($uuid);

                try {
                    $openWa->registerWebhook($uuid, url('/api/webhooks/openwa'));
                } catch (\Throwable $webhookEx) {
                    \Log::warning("Failed to register webhook for legacy session: " . $webhookEx->getMessage());
                }
            } catch (\Throwable $e) {
                \Log::error("Failed to auto-create OpenWA session for legacy instance: " . $e->getMessage());
                return;
            }
        }

        try {
            $statusData = $openWa->getSessionStatus($this->whatsappInstance->instance_id);
            $openWaStatus = $statusData['status'] ?? 'disconnected';

            $localStatus = match ($openWaStatus) {
                'ready' => StatusConnectionEnum::OPEN,
                'initializing', 'authenticating' => StatusConnectionEnum::CONNECTING,
                default => StatusConnectionEnum::CLOSE,
            };

            if ($this->whatsappInstance->status !== $localStatus) {
                $this->whatsappInstance->update([
                    'status' => $localStatus,
                ]);

                if ($localStatus === StatusConnectionEnum::OPEN) {
                    $this->showQr = false;
                    $this->qrCode = null;
                    $this->dispatch('instance-connected');
                    return;
                }
            }

            if ($this->showQr && $openWaStatus === 'qr_ready') {
                $this->qrCode = $openWa->getQrCode($this->whatsappInstance->instance_id);
            } else {
                $this->qrCode = null;
            }
        } catch (\Throwable $e) {
            $this->whatsappInstance->update([
                'status' => StatusConnectionEnum::CLOSE,
            ]);
            $this->qrCode = null;
        }
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

        $this->checkStatus();

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

        try {
            app(OpenWaService::class)->startSession($this->whatsappInstance->instance_id);
        } catch (\Throwable $e) {
            // Ignorar si ya está iniciado
        }

        $this->checkStatus();
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
