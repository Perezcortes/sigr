<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LeadWebhookController extends Controller
{
    public function handle(Request $request)
    {
        try {
            // Loguear para depuración (opcional, sirve para ver qué llega)
            Log::info('Webhook Nocnok recibido:', $request->all());

            // Crear el Lead mapeando los campos de Nocnok a la BD
            $lead = Lead::create([
                'nombre'           => $request->input('ContactName') ?? 'Desconocido',
                'correo'           => $request->input('ContactEmail'),
                'telefono'         => $request->input('ContactPhone'),
                'mensaje'          => $request->input('ContactMessage'),
                'origen'           => $request->input('ContactOrigin') ?? 'Nocnok',
                'url_propiedad'    => $request->input('PropertyUrl'),
                'etapa'            => 'no_contactado', // Por defecto siempre entra así
                'payload_original' => $request->all(), // Guardamos todo el JSON por seguridad
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Lead guardado correctamente',
                'id' => $lead->id
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error en Webhook Nocnok: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el lead'
            ], 500);
        }
    }
}