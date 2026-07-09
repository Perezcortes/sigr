<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SolicitudesPolizaLog;
use Illuminate\Support\Facades\Log;

class PolizaWebhookController extends Controller
{
    public function handle(Request $request)
    {
        Log::info('Webhook de Póliza recibido', $request->all());

        // Extraemos los datos que sabemos que Jona manda
        $externalRef = $request->input('external_reference');
        $status = $request->input('status'); // Jona manda 'COMPLETED' o 'FAILED'

        if (!$externalRef) {
            return response()->json(['status' => 'error', 'message' => 'Falta external_reference'], 400);
        }

        $log = SolicitudesPolizaLog::where('external_reference', $externalRef)->first();

        if ($log) {
            $log->update([
                'status' => $status === 'COMPLETED' ? 'completado' : 'fallido',
                'mensaje_webhook' => json_encode($request->all())
            ]);
            
            // Opcional Si se necesita actualizar la tabla original 'Rents'
            // $renta = $log->rent;
            // $renta->update(['estatus' => '...']);
        }

        // Le contestamos a Jona el JSON para que su panel se ponga verde "success"
        return response()->json([
            'status' => 'success',
            'message' => 'Notificación procesada exitosamente en Rentas.com'
        ]);
    }
}