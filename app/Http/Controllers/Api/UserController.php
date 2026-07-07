<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    private const NOTIFICATION_FIELDS = [
        'notif_recordatorios_email',
        'notif_recordatorios_push',
        'notif_recordatorios_whatsapp',
        'notif_reporte_pago_email',
        'notif_reporte_pago_push',
        'notif_reporte_pago_whatsapp',
        'notif_mensajes_email',
        'notif_mensajes_push',
        'notif_mensajes_whatsapp',
        'notif_mantenimiento_email',
        'notif_mantenimiento_push',
        'notif_mantenimiento_whatsapp',
    ];

    // Lee las 12 preferencias de notificación del usuario autenticado
    public function notifications(Request $request)
    {
        $user = $request->user();

        $data = collect(self::NOTIFICATION_FIELDS)
            ->mapWithKeys(fn (string $field) => [$field => (bool) ($user->{$field} ?? true)])
            ->toArray();

        return response()->json(['data' => $data]);
    }

    // Actualiza las 12 preferencias de notificación del usuario autenticado
    public function updateNotifications(Request $request)
    {
        $validated = $request->validate(array_fill_keys(self::NOTIFICATION_FIELDS, 'required|boolean'));

        $request->user()->update($validated);

        return response()->json(['message' => 'Preferencias actualizadas.']);
    }

    // Activa/desactiva los roles propietario/inquilino del usuario autenticado
    public function updateTipoPerfil(Request $request)
    {
        $validated = $request->validate([
            'is_owner' => 'required|boolean',
            'is_tenant' => 'required|boolean',
        ]);

        if (! $validated['is_owner'] && ! $validated['is_tenant']) {
            return response()->json([
                'message' => 'Debes mantener al menos un perfil activo (propietario o inquilino).',
            ], 422);
        }

        $request->user()->update($validated);

        return response()->json([
            'message' => 'Perfil actualizado.',
            'data' => [
                'is_owner' => $request->user()->is_owner,
                'is_tenant' => $request->user()->is_tenant,
            ],
        ]);
    }
}
