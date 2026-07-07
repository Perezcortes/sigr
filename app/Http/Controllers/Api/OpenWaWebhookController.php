<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\WhatsappInstance;
use App\Models\WhatsappMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class OpenWaWebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        $data = $request->all();

        Log::info('OpenWA Webhook Received', $data);

        $event = $data['event'] ?? null;
        $sessionId = $data['sessionId'] ?? null;
        $payload = $data['payload'] ?? null;

        if ($event !== 'message.received' || !$sessionId || !$payload) {
            return response('Ignored event', 200);
        }

        // Buscar instancia por session ID (instance_id)
        $instance = WhatsappInstance::where('instance_id', $sessionId)->first();
        if (!$instance) {
            Log::warning("OpenWA Webhook: No instance found for session ID {$sessionId}");
            return response('No instance found', 200);
        }

        $rawPhone = preg_replace('/\D/', '', (string) ($payload['from'] ?? ''));
        if (!$rawPhone) {
            return response('No phone found', 200);
        }

        $messageId = $payload['id'] ?? null;
        if (!$messageId) {
            return response('No message ID found', 200);
        }

        // Evitar duplicados
        if (WhatsappMessage::where('wa_message_id', $messageId)->exists()) {
            return response('Message already processed', 200);
        }

        $leadId = $this->resolveLeadId($rawPhone);

        WhatsappMessage::create([
            'instance_id'   => $instance->id,
            'message_id'    => $messageId,
            'remote_jid'    => ($payload['from'] ?? $rawPhone . '@c.us'),
            'wa_message_id' => $messageId,
            'phone'         => $rawPhone,
            'direction'     => 'in',
            'body'          => $payload['body'] ?? '',
            'content'       => $payload['body'] ?? '',
            'lead_id'       => $leadId,
            'user_id'       => null,
            'sent_at'       => now(),
        ]);

        return response('OK', 200);
    }

    private function resolveLeadId(string $phone): ?int
    {
        $candidates = array_unique(array_filter([
            $phone,
            '+' . $phone,
            str_starts_with($phone, '521') ? '52' . substr($phone, 3) : null,
            (str_starts_with($phone, '52') && ! str_starts_with($phone, '521')) ? '521' . substr($phone, 2) : null,
        ]));

        $lead = Lead::whereIn('telefono', $candidates)->first();

        if (! $lead) {
            $local = strlen($phone) >= 12 ? substr($phone, -10) : null;
            if ($local) {
                $lead = Lead::where('telefono', 'LIKE', "%{$local}")->first();
            }
        }

        return $lead?->id;
    }
}
