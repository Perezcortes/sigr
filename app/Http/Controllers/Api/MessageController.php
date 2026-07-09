<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Rent;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    // Lista los mensajes del hilo de una renta (mismo hilo que ya usa ChatManager en Filament)
    public function index(Request $request, int $id)
    {
        $rent = $this->resolveRent($request, $id);
        if (! $rent) {
            return response()->json(['message' => 'Renta no encontrada.'], 404);
        }

        // Marca como vistos los mensajes de los demás, igual que ChatManager::markAsRead()
        Message::where('rent_id', $id)
            ->where('user_id', '!=', $request->user()->id)
            ->where('visto', false)
            ->update(['visto' => true]);

        $messages = Message::with('user')
            ->where('rent_id', $id)
            ->orderBy('created_at')
            ->get()
            ->map(fn ($m) => $this->toArray($m, $request->user()->id));

        return response()->json(['data' => $messages]);
    }

    // Envía un mensaje nuevo en el hilo de una renta
    public function store(Request $request, int $id)
    {
        $rent = $this->resolveRent($request, $id);
        if (! $rent) {
            return response()->json(['message' => 'Renta no encontrada.'], 404);
        }

        $data = $request->validate([
            'cuerpo' => 'required|string|max:1000',
        ]);

        $message = Message::create([
            'rent_id' => $id,
            'user_id' => $request->user()->id,
            'cuerpo' => $data['cuerpo'],
            'visto' => false,
        ]);

        return response()->json(['data' => $this->toArray($message->load('user'), $request->user()->id)], 201);
    }

    // Resuelve la renta si el usuario autenticado es el propietario o el inquilino de esa renta
    private function resolveRent(Request $request, int $id): ?Rent
    {
        $owner = $request->user()->owner;
        $tenant = $request->user()->tenant;

        if (! $owner && ! $tenant) {
            return null;
        }

        return Rent::where('id', $id)
            ->where(function ($q) use ($owner, $tenant) {
                if ($owner) {
                    $q->orWhere('owner_id', $owner->id);
                }
                if ($tenant) {
                    $q->orWhere('tenant_id', $tenant->id);
                }
            })
            ->first();
    }

    // Forma el payload que consume la app para cada mensaje
    private function toArray(Message $message, int $currentUserId): array
    {
        return [
            'id' => $message->id,
            'cuerpo' => $message->cuerpo,
            'mio' => $message->user_id === $currentUserId,
            'autor' => $message->user->name ?? 'Usuario',
            'fecha' => $message->created_at->locale('es')->isoFormat('DD/MM/YYYY'),
            'hora' => $message->created_at->format('H:i'),
            'visto' => (bool) $message->visto,
        ];
    }
}
