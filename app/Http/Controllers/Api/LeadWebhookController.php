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

    /** Tamaño máximo del cuerpo guardado en JSON (evita filas enormes). */
    private const RAW_BODY_DB_MAX_BYTES = 1048576;

    public function handle(Request $request)
    {
        $capture = $this->captureIncomingPayload($request);
        $this->logIncomingWebhook($request, $capture);

        $input = $this->mergedWebhookInput($request, $capture);

        try {
            $lead = Lead::create([
                'nombre' => $input['ContactName'] ?? 'Desconocido',
                'correo' => $input['ContactEmail'] ?? null,
                'telefono' => $input['ContactPhone'] ?? null,
                'mensaje' => $input['ContactMessage'] ?? null,
                'canal' => LeadCanal::Nocnok,
                'origen' => $input['ContactOrigin'] ?? 'Nocnok',
                'url_propiedad' => $input['PropertyUrl'] ?? null,
                'etapa' => 'no_contactado',
                'payload_original' => $capture,
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
     * @param  array<string, mixed>  $capture
     */
    private function logIncomingWebhook(Request $request, array $capture): void
    {
        if (! filter_var(env('NOCNOK_WEBHOOK_LOG', true), FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        $rawForLog = $capture['raw_body'] ?? null;
        if (is_string($rawForLog) && strlen($rawForLog) > self::RAW_BODY_LOG_MAX_BYTES) {
            $rawForLog = substr($rawForLog, 0, self::RAW_BODY_LOG_MAX_BYTES).'… [truncado para log]';
        }

        Log::channel('nocnok_webhook')->info('Nocnok webhook: petición recibida', [
            'ip' => $request->ip(),
            'method' => $request->method(),
            'path' => $request->path(),
            'full_url' => $request->fullUrl(),
            'content_type' => $request->header('Content-Type'),
            'content_length' => $request->header('Content-Length'),
            'user_agent' => $request->userAgent(),
            'query' => $capture['query'] ?? [],
            'request_all' => $capture['request_all'] ?? [],
            'json_decoded_from_raw' => $capture['json_decoded_from_raw_body'] ?? null,
            'raw_body' => $rawForLog,
            'headers' => $this->sanitizedHeaders($request),
        ]);
    }

    /**
     * Lo que llegó antes de mapear a columnas (BD + depuración).
     *
     * @return array<string, mixed>
     */
    private function captureIncomingPayload(Request $request): array
    {
        $raw = $request->getContent();
        $rawLen = strlen($raw);
        $rawForDb = $raw;
        $rawTruncated = false;
        if ($rawLen > self::RAW_BODY_DB_MAX_BYTES) {
            $rawForDb = substr($raw, 0, self::RAW_BODY_DB_MAX_BYTES).'… [truncado, '.$rawLen.' bytes totales]';
            $rawTruncated = true;
        }

        $decoded = null;
        if ($raw !== '') {
            try {
                $d = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
                $decoded = is_array($d) ? $d : ['_root' => $d];
            } catch (\JsonException) {
                $decoded = null;
            }
        }

        return [
            'captured_at' => now()->toIso8601String(),
            'content_type' => $request->header('Content-Type'),
            'query' => $request->query->all(),
            'request_all' => $request->all(),
            'json_decoded_from_raw_body' => $decoded,
            'raw_body' => $rawForDb !== '' ? $rawForDb : null,
            'raw_body_truncated' => $rawTruncated,
            'raw_body_length' => $rawLen,
        ];
    }

    /**
     * Combina el JSON del body (aunque Laravel no lo haya fusionado) con la petición.
     *
     * @param  array<string, mixed>  $capture
     * @return array<string, mixed>
     */
    private function mergedWebhookInput(Request $request, array $capture): array
    {
        $fromBody = $capture['json_decoded_from_raw_body'] ?? null;
        if (! is_array($fromBody)) {
            return $request->all();
        }

        return array_merge($fromBody, $request->all());
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
