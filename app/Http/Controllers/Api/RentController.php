<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rent;
use Illuminate\Http\Request;

class RentController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $owner = $user->owner;

        if (!$owner) {
            return response()->json(['data' => []]);
        }

        $rents = Rent::with(['property.images'])
            ->where('owner_id', $owner->id)
            ->where('estatus', 'activa')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($rent) => $this->toList($rent));

        return response()->json(['data' => $rents]);
    }

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
            'foto'               => $portada?->path_file ? \Storage::disk('spaces')->url($portada->path_file) : null,
            'recamaras'          => (int) ($rent->property?->recamaras ?? 0),
            'm2'                 => (int) ($rent->property?->metros_cuadrados ?? 0),
            'mensajes_no_leidos' => 0,
        ];
    }
}
