<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rent;
use Illuminate\Http\Request;

class RentController extends Controller
{
    // Lista las rentas activas del propietario autenticado
    public function index(Request $request)
    {
        $user = $request->user();

        $owner = $user->owner;

        if (!$owner) {
            return response()->json(['data' => []]);
        }

        // solo rentas activas; vencidas, canceladas y demás estatus no se muestran en la app
        $rents = Rent::with(['property.images'])
            ->where('owner_id', $owner->id)
            ->where('estatus', 'activa')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($rent) => $this->toList($rent));

        return response()->json(['data' => $rents]);
    }

    // Retorna el detalle completo de una renta (solo si pertenece al propietario autenticado)
    public function show(Request $request, int $id)
    {
        $user = $request->user();
        $owner = $user->owner;

        if (!$owner) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $rent = Rent::with(['property.images'])
            ->where('id', $id)
            ->where('owner_id', $owner->id)
            ->first();

        if (!$rent) {
            return response()->json(['message' => 'Renta no encontrada.'], 404);
        }

        return response()->json(['data' => $this->toDetail($rent)]);
    }

    // Cambia el estatus de la renta a 'vencida' para quitarla de la vista del propietario
    public function finalizar(Request $request, int $id)
    {
        $user = $request->user();
        $owner = $user->owner;

        if (!$owner) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $rent = Rent::where('id', $id)->where('owner_id', $owner->id)->first();

        if (!$rent) {
            return response()->json(['message' => 'Renta no encontrada.'], 404);
        }

        $rent->update(['estatus' => 'vencida']);

        return response()->json(['message' => 'Renta finalizada.']);
    }

    // Actualiza los 12 campos de preferencias de notificación de una renta
    public function updateNotifications(Request $request, int $id)
    {
        $user  = $request->user();
        $owner = $user->owner;

        if (!$owner) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $rent = Rent::where('id', $id)->where('owner_id', $owner->id)->first();

        if (!$rent) {
            return response()->json(['message' => 'Renta no encontrada.'], 404);
        }

        $validated = $request->validate([
            'notif_recordatorios_email'    => 'required|boolean',
            'notif_recordatorios_push'     => 'required|boolean',
            'notif_recordatorios_whatsapp' => 'required|boolean',
            'notif_reporte_pago_email'     => 'required|boolean',
            'notif_reporte_pago_push'      => 'required|boolean',
            'notif_reporte_pago_whatsapp'  => 'required|boolean',
            'notif_mensajes_email'         => 'required|boolean',
            'notif_mensajes_push'          => 'required|boolean',
            'notif_mensajes_whatsapp'      => 'required|boolean',
            'notif_mantenimiento_email'    => 'required|boolean',
            'notif_mantenimiento_push'     => 'required|boolean',
            'notif_mantenimiento_whatsapp' => 'required|boolean',
        ]);

        $rent->update($validated);

        return response()->json(['message' => 'Preferencias actualizadas.']);
    }

    // Forma el payload completo para la vista de detalle de renta
    private function toDetail($rent): array
    {
        $portada = $rent->property?->images->firstWhere('is_portada', true)
            ?? $rent->property?->images->first();

        return [
            'id'               => $rent->id,
            'folio'            => $rent->folio,
            'tipo_inmueble'    => $rent->tipo_inmueble,
            'uso_suelo'        => $rent->property?->uso_suelo,
            'renta'            => (float) $rent->renta,
            'fecha_inicio'     => $rent->start_date ? \Carbon\Carbon::parse($rent->start_date)->locale('es')->isoFormat('D [de] MMMM [del] Y') : null,
            'fecha_fin'        => $rent->end_date ? \Carbon\Carbon::parse($rent->end_date)->locale('es')->isoFormat('D [de] MMMM [del] Y') : null,
            'direccion'        => collect([$rent->calle, $rent->colonia, $rent->municipio, $rent->estado])->filter()->implode(', '),
            'foto'             => $portada?->path_file ? \Storage::disk('spaces')->url($portada->path_file) : null,
            'recamaras'        => (int) ($rent->property?->recamaras ?? 0),
            'm2'               => (int) ($rent->property?->metros_cuadrados ?? 0),
            'dia_cobro'        => $rent->dia_cobro_renta,
            'plazo'            => $rent->plazo_arrendamiento,
            'frecuencia_pago'  => $rent->payment_frequency,
        ] + $this->notifFields($rent);
    }

    // Forma el payload resumido para la tarjeta en el listado de mis-rentas
    // Incluye los campos notif_ para que perfil.blade.php pueda inicializar los toggles sin llamadas extra
    private function toList($rent): array
    {
        $portada = $rent->property?->images->firstWhere('is_portada', true)
            ?? $rent->property?->images->first();

        return [
            'id'               => $rent->id,
            'folio'            => $rent->folio,
            'tipo_inmueble'    => $rent->tipo_inmueble,
            'renta'            => (float) $rent->renta,
            'fecha_fin'        => $rent->end_date ? \Carbon\Carbon::parse($rent->end_date)->locale('es')->isoFormat('D [de] MMMM [del] Y') : null,
            'direccion'        => collect([$rent->calle, $rent->colonia, $rent->municipio, $rent->estado])->filter()->implode(', '),
            'foto'             => $portada?->path_file ? \Storage::disk('spaces')->url($portada->path_file) : null,
            'recamaras'        => (int) ($rent->property?->recamaras ?? 0),
            'm2'               => (int) ($rent->property?->metros_cuadrados ?? 0),
            'mensajes_no_leidos' => 0,
        ] + $this->notifFields($rent);
    }

    // Extrae los 12 booleans de notificación de una renta como array
    private function notifFields($rent): array
    {
        return [
            'notif_recordatorios_email'    => (bool) $rent->notif_recordatorios_email,
            'notif_recordatorios_push'     => (bool) $rent->notif_recordatorios_push,
            'notif_recordatorios_whatsapp' => (bool) $rent->notif_recordatorios_whatsapp,
            'notif_reporte_pago_email'     => (bool) $rent->notif_reporte_pago_email,
            'notif_reporte_pago_push'      => (bool) $rent->notif_reporte_pago_push,
            'notif_reporte_pago_whatsapp'  => (bool) $rent->notif_reporte_pago_whatsapp,
            'notif_mensajes_email'         => (bool) $rent->notif_mensajes_email,
            'notif_mensajes_push'          => (bool) $rent->notif_mensajes_push,
            'notif_mensajes_whatsapp'      => (bool) $rent->notif_mensajes_whatsapp,
            'notif_mantenimiento_email'    => (bool) $rent->notif_mantenimiento_email,
            'notif_mantenimiento_push'     => (bool) $rent->notif_mantenimiento_push,
            'notif_mantenimiento_whatsapp' => (bool) $rent->notif_mantenimiento_whatsapp,
        ];
    }
}
