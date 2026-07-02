<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rent;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TicketController extends Controller
{
    // Lista los reportes de mantenimiento de una renta
    public function index(Request $request, int $id)
    {
        $owner = $request->user()->owner;
        if (! $owner) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $rent = Rent::where('id', $id)->where('owner_id', $owner->id)->first();
        if (! $rent) {
            return response()->json(['message' => 'Renta no encontrada.'], 404);
        }

        $tickets = Ticket::with('user')
            ->where('rent_id', $id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($ticket) => $this->toArray($ticket));

        return response()->json(['data' => $tickets]);
    }

    // Crea un nuevo reporte de mantenimiento
    public function store(Request $request, int $id)
    {
        $owner = $request->user()->owner;
        if (! $owner) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $rent = Rent::where('id', $id)->where('owner_id', $owner->id)->first();
        if (! $rent) {
            return response()->json(['message' => 'Renta no encontrada.'], 404);
        }

        $data = $request->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'evidencia_base64' => 'nullable|string',
            'evidencia_mime' => 'nullable|string',
        ]);

        $evidenciaPath = null;
        if (!empty($data['evidencia_base64']) && !empty($data['evidencia_mime'])) {
            $content = base64_decode(preg_replace('/^data:[^;]+;base64,/', '', $data['evidencia_base64']));
            if ($content !== false && $this->validateEvidenciaContent($content, $data['evidencia_mime'])) {
                $ext = match (strtolower($data['evidencia_mime'])) {
                    'image/png' => 'png',
                    'image/webp' => 'webp',
                    default => 'jpg',
                };
                $evidenciaPath = 'mantenimiento/' . uniqid() . '.' . $ext;
                Storage::disk('public')->put($evidenciaPath, $content);
            }
        }

        $ticket = Ticket::create([
            'rent_id' => $id,
            'user_id' => $request->user()->id,
            'titulo' => $data['titulo'],
            'descripcion' => $data['descripcion'] ?? null,
            'estatus' => 'nueva',
            'evidencia' => $evidenciaPath,
        ]);

        return response()->json(['data' => $this->toArray($ticket->load('user'))], 201);
    }

    // Verifica que el contenido decodificado coincida con el MIME declarado (magic bytes)
    private function validateEvidenciaContent(string $content, string $mime): bool
    {
        if (strlen($content) < 8) {
            return false;
        }
        $header = substr($content, 0, 8);
        return match ($mime) {
            'image/jpeg' => str_starts_with($header, "\xFF\xD8\xFF"),
            'image/png' => str_starts_with($header, "\x89PNG\r\n\x1a\n"),
            'image/webp' => str_starts_with($header, 'RIFF') && substr($content, 8, 4) === 'WEBP',
            default => false,
        };
    }

    // Actualiza el estatus de un reporte de mantenimiento
    public function update(Request $request, int $id)
    {
        $owner = $request->user()->owner;
        if (! $owner) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $ticket = Ticket::whereHas('rent', fn ($q) => $q->where('owner_id', $owner->id))
            ->with('user')
            ->find($id);

        if (! $ticket) {
            return response()->json(['message' => 'Reporte no encontrado.'], 404);
        }

        $data = $request->validate([
            'estatus' => 'required|string|in:nueva,en_proceso,completada',
        ]);

        $ticket->update($data);

        return response()->json(['data' => $this->toArray($ticket)]);
    }

    // Forma el payload que consume la app para cada reporte
    private function toArray(Ticket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'titulo' => $ticket->titulo,
            'descripcion' => $ticket->descripcion,
            'estatus' => $ticket->estatus,
            'evidencia_url' => $ticket->evidencia ? Storage::disk('public')->url($ticket->evidencia) : null,
            'reportado_por' => $ticket->user->name ?? 'Usuario',
            'fecha' => \Carbon\Carbon::parse($ticket->created_at)->locale('es')->isoFormat('D/MM/YY, h:mm a'),
        ];
    }
}
