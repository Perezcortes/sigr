<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\DeleteAccountCodeMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

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

    private const AVATAR_MIME_EXTENSIONS = [
        'jpeg' => 'jpg',
        'jpg' => 'jpg',
        'png' => 'png',
        'webp' => 'webp',
        'heic' => 'heic',
    ];

    // Reemplaza la foto de perfil del usuario autenticado (colección 'profile-images',
    // misma que usa Filament en EditProfile.php para el staff)
    public function updateAvatar(Request $request)
    {
        $request->validate([
            'foto_base64' => ['required', 'string'],
        ]);

        $base64 = $request->input('foto_base64');

        // addMediaFromBase64() sin nombre explícito guarda sin extensión (bug de Spatie)
        $extension = 'jpg';
        if (preg_match('#^data:image/(\w+);base64,#i', $base64, $matches)) {
            $extension = self::AVATAR_MIME_EXTENSIONS[strtolower($matches[1])] ?? 'jpg';
        }

        $user = $request->user();
        $user->clearMediaCollection('profile-images');
        $user->addMediaFromBase64($base64)
            ->usingFileName("avatar.{$extension}")
            ->toMediaCollection('profile-images');

        return response()->json([
            'message' => 'Foto de perfil actualizada.',
            'data' => [
                'foto' => $user->fresh()->foto,
            ],
        ]);
    }

    private const DELETE_ACCOUNT_CODE_TTL_MINUTES = 15;

    private function deleteAccountCacheKey(int $userId): string
    {
        return "delete_account_code_{$userId}";
    }

    // Genera un código de 5 dígitos, lo guarda temporalmente y lo manda por correo
    public function requestAccountDeletion(Request $request)
    {
        $user = $request->user();

        $code = str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT);

        Cache::put($this->deleteAccountCacheKey($user->id), $code, now()->addMinutes(self::DELETE_ACCOUNT_CODE_TTL_MINUTES));

        Mail::to($user->email)->send(new DeleteAccountCodeMail($user, $code));

        return response()->json(['message' => 'Código enviado a tu correo.']);
    }

    // Valida el código enviado por correo y, si coincide, elimina (soft delete) la cuenta
    public function confirmAccountDeletion(Request $request)
    {
        $validated = $request->validate([
            'codigo' => ['required', 'string'],
        ]);

        $user = $request->user();
        $cacheKey = $this->deleteAccountCacheKey($user->id);
        $storedCode = Cache::get($cacheKey);

        if (! $storedCode || $storedCode !== $validated['codigo']) {
            return response()->json(['message' => 'El código no es válido o expiró.'], 422);
        }

        Cache::forget($cacheKey);
        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => 'Cuenta eliminada.']);
    }
}
