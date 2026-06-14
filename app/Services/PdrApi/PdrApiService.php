<?php

namespace App\Services\PdrApi;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Services\PdrApi\Mappers\InquilinoMapper;
use App\Services\PdrApi\Mappers\PropietarioMapper;
use App\Services\PdrApi\Mappers\FiadorMapper;
use App\Models\SolicitudesPolizaLog; 

class PdrApiService
{
    protected string $baseUrl;
    protected string $clientId;
    protected string $clientSecret;
    protected string $scope;

    public function __construct()
    {
        $this->baseUrl = env('PDR_API_URL', 'https://pruebas.polizaderentas.com'); // Entorno de pruebas
        $this->clientId = env('PDR_CLIENT_ID', '');
        $this->clientSecret = env('PDR_CLIENT_SECRET', '');
        $this->scope = env('PDR_SCOPE', 'poliza.crear');
    }

    private function obtenerToken(): string
    {
        return Cache::remember('pdr_api_token', 1500, function () {
            $response = Http::asForm()->post($this->baseUrl . '/oauth/token', [
                'grant_type'    => 'client_credentials',
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
                'scope'         => $this->scope,
            ]);

            if ($response->successful()) {
                return $response->json('access_token');
            }

            Log::error('Fallo al obtener Token OAuth2 de PDR: ' . $response->body());
            throw new \Exception('No se pudo autenticar con la API de Póliza de Rentas.');
        });
    }

    public function enviarExpedienteYActualizar($rentaRecord, array $payloadValidacion): array
    {
        $jsonPayload = $this->armarJsonEstructurado($rentaRecord, $payloadValidacion);
        $externalReference = $jsonPayload['external_reference'];

        Log::info('Enviando JSON a PDR:', $jsonPayload);

        $log = SolicitudesPolizaLog::create([
            'rent_id' => $rentaRecord->id,
            'external_reference' => $externalReference,
            'payload_enviado' => json_encode($jsonPayload),
            'status' => 'enviado'
        ]);

        try {
            $token = $this->obtenerToken();
            $idempotencyKey = (string) Str::uuid(); 

            $response = Http::withToken($token)
                ->withHeaders([
                    'Idempotency-Key' => $idempotencyKey,
                    'Accept' => 'application/json'
                ])
                ->post($this->baseUrl . '/api/v1/solicitudes', $jsonPayload); 

            if (!$response->successful()) {
                Log::error('Error de validación PDR API: ' . $response->body());
                $log->update(['status' => 'fallido', 'mensaje_error' => $response->body()]);
                return ['success' => false, 'error' => 'La API de Póliza de Rentas rechazó la solicitud.'];
            }

            // Si PDR respondió 200/201 (Ok)
            $log->update(['status' => 'procesando']);

        } catch (\Exception $e) {
            Log::error('Excepción PDR API: ' . $e->getMessage());
            $log->update(['status' => 'fallido', 'mensaje_error' => $e->getMessage()]);
            return ['success' => false, 'error' => 'No se pudo conectar con el servidor.'];
        }

        try {
            $rentaRecord->update([
                'plazo_arrendamiento' => $payloadValidacion['plazo_arrendamiento'],
                'start_date' => $payloadValidacion['start_date'],
                'end_date' => $payloadValidacion['end_date'],
                'fecha_firma' => $payloadValidacion['fecha_firma'],
                'tipo_poliza' => $payloadValidacion['tipo_poliza'],
                'estatus' => 'analisis',
            ]);
            return ['success' => true];
        } catch (\Exception $e) {
            Log::error('Error actualizando Renta local: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Error al actualizar base de datos local.'];
        }
    }

    private function armarJsonEstructurado($rentaRecord, array $payloadValidacion): array
    {
        $tenantRequest = \App\Models\TenantRequest::where('tenant_id', $rentaRecord->tenant_id)->where('rent_id', $rentaRecord->id)->first();
        $inquilinoMapeado = $tenantRequest ? InquilinoMapper::mapear($tenantRequest, $rentaRecord) : [];

        $ownerRequest = \App\Models\OwnerRequest::where('owner_id', $rentaRecord->owner_id)->where('rent_id', $rentaRecord->id)->first();
        $propietarioMapeado = $ownerRequest ? PropietarioMapper::mapear($ownerRequest, $rentaRecord) : [];

        $fiadorMapeado = null;
        if (strtolower($rentaRecord->tiene_fiador ?? 'no') === 'si') {
            $guarantorRequest = \App\Models\GuarantorRequest::where('rent_id', $rentaRecord->id)->first();
            $fiadorMapeado = $guarantorRequest ? FiadorMapper::mapear($guarantorRequest, $rentaRecord) : [];
        }

        $callbackUrl = url('/api/webhooks/poliza-status');

        return [
            'external_reference' => 'RENTA-' . $rentaRecord->id . '-' . time(), 
            'tipoInmueble'       => $rentaRecord->tipo_inmueble === 'residencial' ? 'Inmuebles Residenciales' : 'Inmuebles Comerciales',
            'tipoPoliza'         => $payloadValidacion['tipo_poliza'],
            'idUser'             => (string) auth()->id(), 
            'idSuc'              => 'SUC-DEFAULT',
            'idInmo'             => 'INMO-DEFAULT', 
            'renta'              => (float) $rentaRecord->precio_renta,
            'callback_url'       => $callbackUrl,

            'inquilino'          => empty($inquilinoMapeado) ? null : $inquilinoMapeado,
            'propietario'        => empty($propietarioMapeado) ? null : $propietarioMapeado,
            'fiador'             => empty($fiadorMapeado) ? null : $fiadorMapeado,
        ];
    }
}