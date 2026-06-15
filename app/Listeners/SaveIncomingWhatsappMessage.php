<?php

namespace App\Listeners;

use App\Models\Lead;
use App\Models\WhatsappMessage;
use WallaceMartinss\FilamentEvolution\Events\MessageReceived;

class SaveIncomingWhatsappMessage
{
    public function handle(MessageReceived $event): void
    {
        $msg = $event->message;

        // Solo procesar mensajes entrantes
        if (! ($msg->direction->value === 'in' || (string) $msg->direction === 'in')) {
            return;
        }

        $rawPhone = preg_replace('/\D/', '', (string) $msg->phone);
        if (! $rawPhone) {
            return;
        }

        // Evitar duplicados
        if (WhatsappMessage::query()->where('wa_message_id', $msg->messageId)->exists()) {
            return;
        }

        $leadId = $this->resolveLeadId($rawPhone);

        WhatsappMessage::create([
            'wa_message_id' => $msg->messageId,
            'phone'         => $rawPhone,
            'direction'     => 'in',
            'body'          => $msg->text ?? '',
            'lead_id'       => $leadId,
            'user_id'       => null,
            'sent_at'       => now(),
        ]);
    }

    private function resolveLeadId(string $phone): ?int
    {
        // Genera variantes para empatar con cualquier formato almacenado
        $candidates = array_unique(array_filter([
            $phone,
            '+' . $phone,
            // Si empieza con 521 → también probar 52 (sin prefijo móvil)
            str_starts_with($phone, '521') ? '52' . substr($phone, 3) : null,
            // Si empieza con 52 (no 521) → también probar 521
            (str_starts_with($phone, '52') && ! str_starts_with($phone, '521')) ? '521' . substr($phone, 2) : null,
        ]));

        $lead = Lead::whereIn('telefono', $candidates)->first();

        if (! $lead) {
            // Intento por 10 dígitos sin código de país
            $local = strlen($phone) >= 12 ? substr($phone, -10) : null;
            if ($local) {
                $lead = Lead::where('telefono', 'LIKE', "%{$local}")->first();
            }
        }

        return $lead?->id;
    }
}
