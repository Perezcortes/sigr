<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Propiedades del propietario autenticado (Sanctum).
 */
class PropertyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $properties = Property::query()
            ->with(['images' => fn ($q) => $q->orderByDesc('is_portada')->orderBy('order')])
            ->where('user_id', $request->user()->id)
            ->orderByDesc('updated_at')
            ->get();

        return response()->json([
            'data' => $properties->map(fn (Property $p) => $this->toListItem($p))->values()->all(),
        ]);
    }

    public function show(Request $request, Property $property): JsonResponse
    {
        $this->ensureOwner($request, $property);

        $property->load(['images' => fn ($q) => $q->orderByDesc('is_portada')->orderBy('order')]);

        return response()->json([
            'data' => $this->toDetail($property),
        ]);
    }

    private function ensureOwner(Request $request, Property $property): void
    {
        if ((int) $property->user_id !== (int) $request->user()->id) {
            abort(403, 'No autorizado.');
        }
    }

    private function toListItem(Property $p): array
    {
        $uso = $p->uso_suelo ?? 'Habitacional';
        $segmento = in_array($uso, ['Comercial', 'Industrial'], true) ? 'comercial' : 'residencial';

        $tipoRaw = $p->tipo_inmueble ?? $p->tipo ?? 'Inmueble';
        $tipoSlug = $this->tipoToSlug((string) $tipoRaw);

        $m2 = $p->getAttribute('metros_cuadrados');
        $recamaras = $p->getAttribute('recamaras');

        return [
            'id' => $p->id,
            'folio' => $p->folio,
            'foto' => $this->coverImageUrl($p) ?? 'https://via.placeholder.com/400x300?text=Sin+imagen',
            'direccion' => $this->direccionLinea($p),
            'm2' => $m2 !== null ? (int) $m2 : 0,
            'recamaras' => $recamaras !== null ? (int) $recamaras : 0,
            'segmento' => $segmento,
            'tipo' => $tipoSlug,
            'tipo_label' => $tipoRaw,
            'renta' => $p->precio_renta !== null ? (float) $p->precio_renta : 0,
            'estatus' => $p->estatus,
        ];
    }

    private function toDetail(Property $p): array
    {
        $item = $this->toListItem($p);

        $imagenes = $p->images->map(function ($img) {
            if (! $img->path_file) {
                return null;
            }

            return url(Storage::disk('public')->url($img->path_file));
        })->filter()->values()->all();

        if ($imagenes === []) {
            $imagenes = [$item['foto']];
        }

        $uso = $p->uso_suelo ?? 'Habitacional';
        $tipo = $p->tipo_inmueble ?? $p->tipo ?? 'Inmueble';
        $tipoPropiedad = $uso.' | '.$tipo;

        return array_merge($item, [
            'imagenes' => $imagenes,
            'tipoPropiedad' => $tipoPropiedad,
            'precioMensual' => $item['renta'],
            'uso_suelo' => $p->uso_suelo,
            'tipo_inmueble' => $p->tipo_inmueble ?? $p->tipo,
        ]);
    }

    private function coverImageUrl(Property $p): ?string
    {
        $images = $p->relationLoaded('images') ? $p->images : $p->images()->orderByDesc('is_portada')->orderBy('order')->get();
        $first = $images->firstWhere('is_portada', true) ?? $images->first();
        if (! $first || ! $first->path_file) {
            return null;
        }

        return url(Storage::disk('public')->url($first->path_file));
    }

    private function direccionLinea(Property $p): string
    {
        if (filled($p->direccion)) {
            return trim((string) $p->direccion);
        }

        $parts = array_filter([
            $p->calle,
            $p->numero_exterior ? '#'.$p->numero_exterior : null,
            $p->colonia,
            $p->delegacion_municipio,
            $p->estado,
        ]);

        return $parts !== [] ? implode(', ', $parts) : ($p->nombre ?? 'Sin dirección');
    }

    private function tipoToSlug(string $tipo): string
    {
        return Str::slug($tipo, '-') ?: 'inmueble';
    }
}
