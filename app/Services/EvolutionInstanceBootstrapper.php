<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\WhatsappInstance;
use WallaceMartinss\FilamentEvolution\Enums\StatusConnectionEnum;
use WallaceMartinss\FilamentEvolution\Exceptions\EvolutionApiException;
use WallaceMartinss\FilamentEvolution\Services\EvolutionClient;

class EvolutionInstanceBootstrapper
{
    public function __construct(
        private EvolutionClient $client
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function instanceOptionsFromModel(WhatsappInstance $record): array
    {
        return [
            'reject_call' => (bool) $record->reject_call,
            'msg_call' => $record->msg_call ?? '',
            'groups_ignore' => (bool) $record->groups_ignore,
            'always_online' => (bool) $record->always_online,
            'read_messages' => (bool) $record->read_messages,
            'read_status' => (bool) $record->read_status,
            'sync_full_history' => (bool) $record->sync_full_history,
        ];
    }

    /**
     * @throws EvolutionApiException
     */
    public function syncInstanceToEvolutionApi(WhatsappInstance $record): void
    {
        $this->client->createInstance(
            instanceName: $record->name,
            number: $record->number,
            qrcode: false,
            options: $this->instanceOptionsFromModel($record)
        );
    }

    /**
     * Crea una instancia local y en Evolution para un asesor y la enlaza al usuario.
     * Si la API falla, el registro local queda guardado y el enlace se mantiene (mismo criterio que el recurso admin).
     */
    public function createAdvisorInstance(User $user, string $number): WhatsappInstance
    {
        $name = $this->generateUniqueInstanceName($user);

        $instance = WhatsappInstance::create([
            'name' => $name,
            'number' => $number,
            'status' => StatusConnectionEnum::CLOSE,
            'reject_call' => config('filament-evolution.instance.reject_call', false),
            'msg_call' => config('filament-evolution.instance.msg_call', ''),
            'groups_ignore' => config('filament-evolution.instance.groups_ignore', false),
            'always_online' => config('filament-evolution.instance.always_online', false),
            'read_messages' => config('filament-evolution.instance.read_messages', false),
            'read_status' => config('filament-evolution.instance.read_status', false),
            'sync_full_history' => config('filament-evolution.instance.sync_full_history', false),
        ]);

        try {
            $this->syncInstanceToEvolutionApi($instance);
        } catch (EvolutionApiException) {
            // Guardado local; el asesor puede reintentar conexión con el QR.
        }

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
