<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\WhatsappInstance;
use WallaceMartinss\FilamentEvolution\Enums\StatusConnectionEnum;
use Exception;

class EvolutionInstanceBootstrapper
{
    public function __construct(
        private OpenWaService $openWa
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function instanceOptionsFromModel(WhatsappInstance $record): array
    {
        return [];
    }

    /**
     * Sincroniza la instancia local creando y arrancando sesión en OpenWA.
     */
    public function syncInstanceToEvolutionApi(WhatsappInstance $record): void
    {
        $session = $this->openWa->createSession($record->name);
        $uuid = $session['id'];

        $this->openWa->startSession($uuid);

        try {
            $this->openWa->registerWebhook($uuid, url('/api/webhooks/openwa'));
        } catch (\Throwable $e) {
            \Log::warning("Failed to register webhook in syncInstanceToEvolutionApi: " . $e->getMessage());
        }

        $record->update([
            'instance_id' => $uuid,
            'status' => StatusConnectionEnum::CONNECTING,
        ]);
    }

    /**
     * Crea una instancia local y en OpenWA para un asesor y la enlaza al usuario.
     */
    public function createAdvisorInstance(User $user, string $number): WhatsappInstance
    {
        $name = $this->generateUniqueInstanceName($user);

        // Crear la sesión en OpenWA
        $session = $this->openWa->createSession($name);
        $uuid = $session['id'];

        // Arrancar la sesión
        $this->openWa->startSession($uuid);

        // Registrar webhook
        try {
            $this->openWa->registerWebhook($uuid, url('/api/webhooks/openwa'));
        } catch (\Throwable $e) {
            \Log::warning("Failed to register webhook in createAdvisorInstance: " . $e->getMessage());
        }

        $instance = WhatsappInstance::create([
            'name' => $name,
            'number' => $number,
            'instance_id' => $uuid,
            'status' => StatusConnectionEnum::CONNECTING,
            'reject_call' => config('filament-evolution.instance.reject_call', false),
            'msg_call' => config('filament-evolution.instance.msg_call', ''),
            'groups_ignore' => config('filament-evolution.instance.groups_ignore', false),
            'always_online' => config('filament-evolution.instance.always_online', false),
            'read_messages' => config('filament-evolution.instance.read_messages', false),
            'read_status' => config('filament-evolution.instance.read_status', false),
            'sync_full_history' => config('filament-evolution.instance.sync_full_history', false),
        ]);

        $user->forceFill(['evolution_whatsapp_instance_id' => $instance->id])->save();

        if (blank($user->whatsapp)) {
            $user->forceFill(['whatsapp' => $number])->save();
        }

        return $instance->fresh();
    }

    protected function generateUniqueInstanceName(User $user): string
    {
        $base = 'wa-user-'.$user->getKey();

        if (! WhatsappInstance::query()->where('name', $base)->exists()) {
            return $base;
        }

        return $base.'-'.substr(md5((string) microtime(true)), 0, 6);
    }
}
