<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->is_owner) {
            abort(403, 'Solo los propietarios pueden crear propiedades.');
        }

        $payload = $request->all();
        foreach (['m2Terreno', 'm2Construccion', 'numeroCuartos', 'numeroOficinas', 'rentaMensual', 'mantenimiento'] as $k) {
            if (array_key_exists($k, $payload) && $payload[$k] === '') {
                $payload[$k] = null;
            }
        }
        $request->merge($payload);

        $data = $request->validate([
            'segmento' => ['required', 'string', Rule::in(['residencial', 'comercial', 'mixto'])],
            'tipoPropiedad' => ['required', 'string', 'max:64'],
            'usoSuelo' => ['nullable', 'string', 'max:32'],
            'direccion' => ['required', 'array'],
            'direccion.calle' => ['required', 'string', 'max:255'],
            'direccion.numero' => ['nullable', 'string', 'max:32'],
            'direccion.colonia' => ['nullable', 'string', 'max:255'],
            'direccion.municipio' => ['nullable', 'string', 'max:255'],
            'direccion.ciudad' => ['nullable', 'string', 'max:255'],
            'direccion.estado' => ['nullable', 'string', 'max:255'],
            'direccion.cp' => ['nullable', 'string', 'max:16'],
            'm2Terreno' => ['nullable', 'numeric', 'min:0'],
            'm2Construccion' => ['nullable', 'numeric', 'min:0'],
            'numeroCuartos' => ['nullable', 'numeric', 'min:0'],
            'numeroOficinas' => ['nullable', 'numeric', 'min:0'],
            'rentaMensual' => ['nullable', 'numeric', 'min:0'],
            'mantenimiento' => ['nullable', 'numeric', 'min:0'],
            'aceptaMascotas' => ['nullable', 'string', Rule::in(['si', 'no'])],
            'inventario' => ['nullable', 'string', 'max:65535'],
        ]);

        $usoSuelo = $this->mapUsoSueloEnum($data['segmento'], $data['usoSuelo'] ?? null);
        $tipoInmueble = $this->mapTipoInmueble($data['tipoPropiedad']);
        $dir = $data['direccion'];

        $m2Terreno = isset($data['m2Terreno']) ? (float) $data['m2Terreno'] : null;
        $m2Constr = isset($data['m2Construccion']) ? (float) $data['m2Construccion'] : null;
        $metros = $m2Constr ?: $m2Terreno;

        $cuartos = isset($data['numeroCuartos']) ? (int) $data['numeroCuartos'] : null;
        $oficinas = isset($data['numeroOficinas']) ? (int) $data['numeroOficinas'] : null;
        $recamaras = $cuartos ?? $oficinas ?? null;

        $refCiudad = trim((string) ($dir['ciudad'] ?? ''));
        $referencias = $refCiudad !== '' ? 'Ciudad: '.$refCiudad : null;

        $property = Property::create([
            'user_id' => $user->id,
            'estatus' => 'disponible',
            'tipo_inmueble' => $tipoInmueble,
            'uso_suelo' => $usoSuelo,
            'mascotas' => ($data['aceptaMascotas'] ?? 'no') === 'si' ? 'si' : 'no',
            'precio_renta' => isset($data['rentaMensual']) && $data['rentaMensual'] !== '' ? $data['rentaMensual'] : null,
            'costo_mantenimiento_mensual' => isset($data['mantenimiento']) && $data['mantenimiento'] !== '' ? $data['mantenimiento'] : null,
            'inventario' => $data['inventario'] ?? null,
            'calle' => $dir['calle'],
            'numero_exterior' => $dir['numero'] ?? null,
            'colonia' => $dir['colonia'] ?? null,
            'delegacion_municipio' => $dir['municipio'] ?? null,
            'estado' => $dir['estado'] ?? null,
            'codigo_postal' => $dir['cp'] ?? null,
            'referencias_ubicacion' => $referencias,
            'metros_cuadrados' => $metros,
            'recamaras' => $recamaras,
        ]);

        $property->load(['images' => fn ($q) => $q->orderByDesc('is_portada')->orderBy('order')]);

        return response()->json([
            'data' => $this->toDetail($property),
            'message' => 'Propiedad creada correctamente.',
        ], 201);
    }

    public function show(Request $request, Property $property): JsonResponse
    {
        $this->ensureOwner($request, $property);

        $property->load(['images' => fn ($q) => $q->orderByDesc('is_portada')->orderBy('order')]);

        return response()->json([
            'data' => $this->toDetail($property),
        ]);
    }

    private function mapUsoSueloEnum(string $segmento, ?string $usoSelect): string
    {
        return match ($segmento) {
            'residencial' => 'Habitacional',
            'comercial' => match ($usoSelect) {
                'residencial' => 'Habitacional',
                'comercial' => 'Comercial',
                'mixto' => 'Comercial',
                default => 'Comercial',
            },
            'mixto' => 'Comercial',
            default => 'Habitacional',
        };
    }

    private function mapTipoInmueble(string $slug): string
    {
        $slug = strtolower($slug);

        return match ($slug) {
            'casa', 'villa' => 'Casa',
            'departamento' => 'Departamento',
            'terreno' => 'Terreno',
            'oficina' => 'Oficina',
            'local' => 'Local comercial',
            'edificio' => 'Oficina',
            'nave_industrial' => 'Nave industrial',
            'consultorio' => 'Consultorio',
            default => 'Casa',
        };
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
