<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class OpenWaService
{
    protected string $baseUrl;
    protected string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.openwa.url', ''), '/');
        $this->apiKey = config('services.openwa.api_key', '');
    }

    /**
     * Helper to perform requests.
     */
    protected function request(string $method, string $path, array $data = [])
    {
        $url = $this->baseUrl . $path;

        $response = Http::withHeaders([
            'X-API-Key' => $this->apiKey,
            'accept' => 'application/json',
        ])
        ->withBody(empty($data) ? null : json_encode($data), 'application/json')
        ->send($method, $url);

        if (!$response->successful()) {
            Log::error("OpenWA API Error [{$response->status()}] to {$path}", [
                'body' => $response->body(),
                'payload' => $data,
            ]);
            throw new Exception("Error de OpenWA: " . ($response->json()['message'] ?? 'Error desconocido (' . $response->status() . ')'));
        }

        return $response->json();
    }

    /**
     * Create a session.
     */
    public function createSession(string $name): array
    {
        return $this->request('POST', '/api/sessions', [
            'name' => $name,
        ]);
    }

    /**
     * Start a session.
     */
    public function startSession(string $uuid): array
    {
        return $this->request('POST', "/api/sessions/{$uuid}/start");
    }

    /**
     * Stop a session.
     */
    public function stopSession(string $uuid): array
    {
        return $this->request('POST', "/api/sessions/{$uuid}/stop");
    }

    /**
     * Delete a session.
     */
    public function deleteSession(string $uuid): void
    {
        $url = $this->baseUrl . "/api/sessions/{$uuid}";
        $response = Http::withHeaders([
            'X-API-Key' => $this->apiKey,
            'accept' => 'application/json',
        ])->delete($url);

        if (!$response->successful()) {
            Log::error("OpenWA API Error [{$response->status()}] to DELETE /api/sessions/{$uuid}", [
                'body' => $response->body(),
            ]);
            throw new Exception("Error de OpenWA al eliminar sesión.");
        }
    }

    /**
     * Get session status.
     */
    public function getSessionStatus(string $uuid): array
    {
        return $this->request('GET', "/api/sessions/{$uuid}");
    }

    /**
     * Get QR code for session.
     */
    public function getQrCode(string $uuid): ?string
    {
        try {
            $data = $this->request('GET', "/api/sessions/{$uuid}/qr");
            return $data['qrCode'] ?? null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Send text message.
     */
    public function sendText(string $uuid, string $to, string $text): array
    {
        $cleanPhone = preg_replace('/\D/', '', $to);
        $chatId = $cleanPhone . '@c.us';

        return $this->request('POST', "/api/sessions/{$uuid}/messages/send-text", [
            'chatId' => $chatId,
            'text' => $text,
        ]);
    }

    /**
     * Send media (image/document) from local path.
     */
    public function sendMedia(string $uuid, string $to, string $filePath, ?string $caption = null, string $disk = 'public'): array
    {
        $cleanPhone = preg_replace('/\D/', '', $to);
        $chatId = $cleanPhone . '@c.us';

        if (!Storage::disk($disk)->exists($filePath)) {
            throw new Exception("El archivo no existe en el disco {$disk}: {$filePath}");
        }

        $fileData = Storage::disk($disk)->get($filePath);
        $mime = Storage::disk($disk)->mimeType($filePath) ?: 'application/octet-stream';
        $base64 = 'data:' . $mime . ';base64,' . base64_encode($fileData);
        $filename = basename($filePath);

        $payload = [
            'chatId' => $chatId,
            'base64' => $base64,
            'filename' => $filename,
            'mimetype' => $mime,
        ];

        if ($caption !== null) {
            $payload['caption'] = $caption;
        }

        $isImage = str_starts_with($mime, 'image/');
        $endpoint = $isImage ? 'send-image' : 'send-document';

        return $this->request('POST', "/api/sessions/{$uuid}/messages/{$endpoint}", $payload);
    }
}
