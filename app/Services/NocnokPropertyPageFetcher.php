<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NocnokPropertyPageFetcher
{
    /**
     * @return array{
     *     imagen_propiedad: ?string,
     *     metros_cuadrados: ?string,
     *     numero_recamaras: ?int,
     *     agent_email: ?string,
     *     agent_name: ?string,
     * }|null
     */
    public function fetchPropertyPageData(string $propertyPageUrl): ?array
    {
        $html = $this->fetchHtml($propertyPageUrl);

        if ($html === null) {
            return null;
        }

        return $this->parseFromHtml($html);
    }

    /**
     * @return array{
     *     imagen_propiedad: ?string,
     *     metros_cuadrados: ?string,
     *     numero_recamaras: ?int,
     *     agent_email: ?string,
     *     agent_name: ?string,
     * }
     */
    public function parseFromHtml(string $html): array
    {
        return [
            'imagen_propiedad' => $this->extractCoverImageFromHtml($html),
            'metros_cuadrados' => $this->extractConstructionSize($html),
            'numero_recamaras' => $this->extractBedrooms($html),
            'agent_email' => $this->extractAgentEmail($html),
            'agent_name' => $this->extractAgentName($html),
        ];
    }

    private function fetchHtml(string $propertyPageUrl): ?string
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; AdmRentas/1.0)',
                    'Accept' => 'text/html,application/xhtml+xml',
                ])
                ->get($propertyPageUrl);

            if (! $response->successful()) {
                Log::channel('nocnok_webhook')->warning('Nocnok página: respuesta HTTP no exitosa', [
                    'url' => $propertyPageUrl,
                    'status' => $response->status(),
                ]);

                return null;
            }

            return $response->body();
        } catch (\Throwable $e) {
            Log::channel('nocnok_webhook')->warning('Nocnok página: error al descargar', [
                'url' => $propertyPageUrl,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function extractCoverImageFromHtml(string $html): ?string
    {
        if (preg_match(
            '/pictureUrls.{0,8}(https:\/\/s3\.amazonaws\.com\/nocnok-img\/pp-[a-zA-Z0-9.-]+)/',
            $html,
            $matches,
        )) {
            return $matches[1];
        }

        if (preg_match('/"pictureUrls"\s*:\s*\[\s*"(https:\/\/[^"]+)"/', $html, $matches)) {
            return $matches[1];
        }

        if (preg_match('/property="og:image"\s+content="([^"]+)"/', $html, $matches)) {
            return $matches[1];
        }

        if (preg_match('/name="twitter:image"\s+content="([^"]+)"/', $html, $matches)) {
            return $matches[1];
        }

        if (preg_match('/class="gallery-box2__styled-image[^"]*"[^>]*\ssrc="([^"]+)"/', $html, $matches)) {
            return $matches[1];
        }

        if (preg_match('/src="(https:\/\/s3\.amazonaws\.com\/nocnok-img\/pp-[^"]+)"[^>]*class="gallery-box2__styled-image/', $html, $matches)) {
            return $matches[1];
        }

        if (preg_match('/https:\/\/s3\.amazonaws\.com\/nocnok-img\/pp-[a-zA-Z0-9.-]+/', $html, $matches)) {
            return $matches[0];
        }

        return null;
    }

    public function extractConstructionSize(string $html): ?string
    {
        if (preg_match('/constructionSize\\\\":(\d+)/', $html, $matches)) {
            return $matches[1];
        }

        if (preg_match('/"constructionSize"\s*:\s*(\d+)/', $html, $matches)) {
            return $matches[1];
        }

        return null;
    }

    public function extractBedrooms(string $html): ?int
    {
        if (preg_match('/bedrooms\\\\":(\d+)/', $html, $matches)) {
            return (int) $matches[1];
        }

        if (preg_match('/"bedrooms"\s*:\s*(\d+)/', $html, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    public function extractAgentEmail(string $html): ?string
    {
        if (preg_match('/agentEmail\\\\":\\\\"([^\\\\"]+)\\\\"/', $html, $matches)) {
            return strtolower(trim($matches[1]));
        }

        if (preg_match('/"agentEmail"\s*:\s*"([^"]+)"/', $html, $matches)) {
            return strtolower(trim($matches[1]));
        }

        return null;
    }

    public function extractAgentName(string $html): ?string
    {
        if (preg_match('/agentName\\\\":\\\\"([^\\\\"]+)\\\\"/', $html, $matches)) {
            return trim($matches[1]);
        }

        if (preg_match('/"agentName"\s*:\s*"([^"]+)"/', $html, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }
}
