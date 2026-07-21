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
        $this->baseUrl      = env('PDR_API_URL', 'https://pruebas.polizaderentas.com');
        $this->clientId     = env('PDR_CLIENT_ID', '');
        $this->clientSecret = env('PDR_CLIENT_SECRET', '');
        $this->scope        = env('PDR_SCOPE', 'poliza.crear');
    }

    private function obtenerToken(): string
    {
        return Cache::remember('pdr_api_token', 1500, function () {
            $response = Http::asForm()
            ->withoutVerifying()
            ->post($this->baseUrl . '/oauth/token', [
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

    // Obtener la lista de Sucursales (Equipos)
    public function obtenerSucursales(): array
    {
        $token = $this->obtenerToken();

        $response = Http::withToken($token)
            ->withoutVerifying()
            ->get($this->baseUrl . '/api/v1/sucursales/nombres');

        if ($response->successful()) {
            $sucursales = $response->json('data.sucursales', []);

            return collect($sucursales)->pluck('nombre', 'id')->toArray();
        }

        Log::error('Error al obtener sucursales PDR: ' . $response->body());
        return [];
    }

    // Obtener la lista de Agentes (Asesores) por Sucursal
    public function obtenerAgentesPorSucursal(string $idSucursalHasheado): array
    {
        $token = $this->obtenerToken();

        $response = Http::withToken($token)
            ->withoutVerifying()
            ->get($this->baseUrl . '/api/v1/sucursal/' . $idSucursalHasheado . '/abogados');

        if ($response->successful()) {
            $agentes = $response->json('data.usuarios', []);

            return collect($agentes)->pluck('nombre', 'id')->toArray();
        }

        Log::error('Error al obtener agentes PDR: ' . $response->body());
        return [];
    }

    public function enviarExpedienteYActualizar($rentaRecord, array $payloadValidacion): array
    {
        $jsonPayload = $this->armarJsonEstructurado($rentaRecord, $payloadValidacion);
        $externalReference = $jsonPayload['external_reference'];

        // Validación Pre-Vuelo para Póliza con Seguro
        if ($payloadValidacion['tipo_poliza'] === 'PÓLIZA CON SEGURO') {
            $datosPersonalesInq = $jsonPayload['inquilino']['datosPersonales'] ?? [];
            
            // 1 significa 'Extranjera' en InquilinoMapper
            if (($datosPersonalesInq['nacionalidad'] ?? 0) === 1) { 
                
                $camposFaltantes = [];
                if (empty($datosPersonalesInq['paispf'])) $camposFaltantes[] = 'País de origen';
                if (empty($datosPersonalesInq['fechaVencimientoTarjeta'])) $camposFaltantes[] = 'Fecha de Vencimiento de Tarjeta';
                if (empty($datosPersonalesInq['nue'])) $camposFaltantes[] = 'NUE';
                if (empty($datosPersonalesInq['tipoResidencia'])) $camposFaltantes[] = 'Tipo de Residencia';

                // Si falta al menos uno, abortamos el envío y le avisamos al admin
                if (!empty($camposFaltantes)) {
                    return [
                        'success' => false,
                        'error' => 'No se puede enviar. Para la PÓLIZA CON SEGURO de un inquilino extranjero, faltan los siguientes datos en su solicitud: ' . implode(', ', $camposFaltantes) . '.'
                    ];
                }
            }
        }

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
                ->withoutVerifying()
                ->asForm()
                ->timeout(90)
                ->withHeaders([
                    'Idempotency-Key' => $idempotencyKey,
                    'Accept' => 'application/json',
                ])
                ->post($this->baseUrl . '/api/v1/poliza/solicitud/crear', $jsonPayload);

            if (!$response->successful()) {
                Log::error('Error de validación PDR API: ' . $response->body());
                $log->update(['status' => 'fallido', 'mensaje_error' => $response->body()]);

                // Decodificamos la respuesta de la API para extraer el error real
                $apiResponse = $response->json();

                // Tomamos el mensaje general ("Error de validación", "Error al procesar...", etc.)
                $mensajeError = $apiResponse['message'] ?? 'La API de Póliza de Rentas rechazó la solicitud sin especificar un motivo.';

                // Si la API devolvió errores específicos por campo, los acumulamos limpiamente
                if (!empty($apiResponse['errors']) && is_array($apiResponse['errors'])) {
                    $detallesCampos = [];
                    foreach ($apiResponse['errors'] as $campo => $mensajesDelCampo) {
                        // Un campo puede tener varios mensajes de error, los unimos
                        $detallesCampos[] = is_array($mensajesDelCampo) ? implode(' ', $mensajesDelCampo) : $mensajesDelCampo;
                    }
                    // Juntamos todos los errores de los campos separados por un salto de línea o punto
                    $mensajeError .= ' Detalles: ' . implode(' ', $detallesCampos);
                }

                return [
                    'success' => false,
                    'error' => $mensajeError 
                ];
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

            \App\Models\RentComment::create([
                'rent_id' => $rentaRecord->id,
                'user_id' => auth()->id(),
                'comment' => "El sistema registró el envío exitoso del expediente a Póliza de Rentas. Procesamiento asíncrono en curso.\n\n• Referencia externa: " . $externalReference . "\n• Folio Póliza de Rentas: Pendiente de asignación automática.\n• Estatus de la operación: cambió a 'Análisis'.",
                'status'  => 'activa',
            ]);

            return ['success' => true];
        } catch (\Exception $e) {
            Log::error('Error actualizando Renta local: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Error al actualizar base de datos local.'];
        }
    }

    private function armarJsonEstructurado($rentaRecord, array $payloadValidacion): array
    {   
        // ASIGNACIÓN TEMPORAL EN MEMORIA:
        // Como la actualización en la base de datos se ejecuta después de enviar el JSON,
        // los mappers leen el valor viejo. Forzamos el nuevo valor en el objeto para el mapeo:
        $rentaRecord->tipo_poliza = $payloadValidacion['tipo_poliza'];
        
        $tenantRequest = \App\Models\TenantRequest::where('tenant_id', $rentaRecord->tenant_id)->where('rent_id', $rentaRecord->id)->first();
        $inquilinoMapeado = $tenantRequest ? InquilinoMapper::mapear($tenantRequest, $rentaRecord) : [];

        $ownerRequest = \App\Models\OwnerRequest::where('owner_id', $rentaRecord->owner_id)->where('rent_id', $rentaRecord->id)->first();
        $propietarioMapeado = $ownerRequest ? PropietarioMapper::mapear($ownerRequest, $rentaRecord) : [];

        // Siempre enviamos el arreglo con la bandera para que Jona sepa si hay o no.
        $tieneFiador = strtolower($rentaRecord->tiene_fiador ?? 'no') === 'si';
        
        $fiadorMapeado = [
            'incluyeFiadorFlag' => $tieneFiador ? 1 : 0
        ];

        // Si sí tiene fiador, le inyectamos el resto de los datos mapeados
        if ($tieneFiador) {
            $guarantorRequest = \App\Models\GuarantorRequest::where('rent_id', $rentaRecord->id)->first();
            if ($guarantorRequest) {
                $datosFiador = FiadorMapper::mapear($guarantorRequest, $rentaRecord);
                $fiadorMapeado = array_merge($fiadorMapeado, $datosFiador);
            }
        }

        $callbackUrl = url('/api/webhooks/poliza-status');
        //$callbackUrl = 'http://192.168.1.85:8000/api/webhooks/poliza-status';

        return [
            'external_reference' => 'RENTA-' . $rentaRecord->id . '-' . time(),
            'tipoInmueble'       => $rentaRecord->tipo_inmueble === 'residencial' ? 'Inmuebles Residenciales' : 'Inmuebles Comerciales',
            'tipoPoliza'         => $payloadValidacion['tipo_poliza'],

            'idUser' => $rentaRecord->pdr_asesor_id,
            'idSuc'  => $rentaRecord->pdr_office_id,
            'idInmo' => '2Volej2RejNm',

            'renta'  => (int) $rentaRecord->precio_renta,
            'callback_url' => $callbackUrl,

            'inquilino'          => empty($inquilinoMapeado) ? null : $inquilinoMapeado,
            'propietario'        => empty($propietarioMapeado) ? null : $propietarioMapeado,
            'fiador'             => empty($fiadorMapeado) ? null : $fiadorMapeado,
        ];
    }


}