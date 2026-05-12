<?php

namespace App\Http\Controllers\Api;

use App\Enums\LeadCanal;
use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LeadWebhookController extends Controller
{
    private const RAW_BODY_LOG_MAX_BYTES = 65536;

    public function handle(Request $request)
    {
        $this->logIncomingWebhook($request);

        try {
            $lead = Lead::create([
                'nombre' => $request->input('ContactName') ?? 'Desconocido',
                'correo' => $request->input('ContactEmail'),
                'telefono' => $request->input('ContactPhone'),
                'mensaje' => $request->input('ContactMessage'),
                'canal' => LeadCanal::Nocnok,
                'origen' => $request->input('ContactOrigin') ?? 'Nocnok',
                'url_propiedad' => $request->input('PropertyUrl'),
                'etapa' => 'no_contactado',
                'payload_original' => $request->all(),
            ]);

            Log::channel('nocnok_webhook')->info('Nocnok webhook: lead creado', [
                'lead_id' => $lead->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Lead guardado correctamente',
                'id' => $lead->id,
            ], 201);
        } catch (\Throwable $e) {
            Log::channel('nocnok_webhook')->error('Nocnok webhook: error al guardar lead', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            Log::error('Error en Webhook Nocnok: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el lead',
            ], 500);
        }
    }

    /**
     * Registra cómo llega la petición (útil si Nocnok envía JSON, form-data o claves distintas).
     */
    private function logIncomingWebhook(Request $request): void
    {
        if (! filter_var(env('NOCNOK_WEBHOOK_LOG', true), FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        $raw = $request->getContent();
        $rawLen = strlen($raw);
        if ($rawLen > self::RAW_BODY_LOG_MAX_BYTES) {
            $raw = substr($raw, 0, self::RAW_BODY_LOG_MAX_BYTES).'… [truncado, '.$rawLen.' bytes totales]';
        }

        $decodedJson = null;
        $ct = (string) $request->header('Content-Type');
        if ($raw !== '' && (str_contains($ct, 'json') || str_starts_with(ltrim($raw), '{') || str_starts_with(ltrim($raw), '['))) {
            try {
                $decodedJson = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                $decodedJson = 'JSON inválido (revisar raw_body)';
            }
        }

        Log::channel('nocnok_webhook')->info('Nocnok webhook: petición recibida', [
            'ip' => $request->ip(),
            'method' => $request->method(),
            'path' => $request->path(),
            'full_url' => $request->fullUrl(),
            'content_type' => $request->header('Content-Type'),
            'content_length' => $request->header('Content-Length'),
            'user_agent' => $request->userAgent(),
            'query' => $request->query->all(),
            'request_all' => $request->all(),
            'json_decoded_from_raw' => $decodedJson,
            'raw_body' => $raw !== '' ? $raw : null,
            'headers' => $this->sanitizedHeaders($request),
        ]);
    }

    /**
     * @return array<string, list<string>|string>
     */
    private function sanitizedHeaders(Request $request): array
    {
        $redact = ['authorization', 'cookie', 'x-api-key', 'php-auth-pw'];

        $out = [];
        foreach ($request->headers->all() as $name => $lines) {
            $lower = strtolower($name);
            if (in_array($lower, $redact, true)) {
                $out[$name] = ['[redacted]'];

                continue;
            }
            $out[$name] = $lines;
        }

        return $out;
    }
}
