<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ApplicationController extends Controller
{
    // Lista las solicitudes del inquilino autenticado, para "Listado de Solicitudes"
    public function index(Request $request)
    {
        if (! $request->user()->is_tenant) {
            return response()->json(['message' => 'Solo los inquilinos pueden ver solicitudes.'], 403);
        }

        $applications = Application::where('user_id', $request->user()->id)
            ->with('documents')
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => $applications->map(fn (Application $a) => $this->toListItem($a))->values()]);
    }

    // Crea la solicitud con lo mínimo
    public function store(Request $request)
    {
        if (! $request->user()->is_tenant) {
            return response()->json(['message' => 'Solo los inquilinos pueden crear solicitudes.'], 403);
        }

        $data = $request->validate([
            'tipo_inmueble' => 'required|string|in:residencial,comercial',
            'tipo_persona' => 'required|string|in:fisica,moral',
        ]);

        $application = Application::create([
            'user_id' => $request->user()->id,
            'tipo_persona' => $data['tipo_persona'],
            'tipo_inmueble' => $data['tipo_inmueble'],
            'estatus' => 'pendiente',
        ]);

        return response()->json(['data' => $this->toDetail($application)], 201);
    }

    // Detalle completo, para retomar el formulario si el usuario recarga a medio llenar
    public function show(Request $request, int $id)
    {
        $application = $this->viewableApplication($request, $id);
        if (! $application) {
            return response()->json(['message' => 'Solicitud no encontrada.'], 404);
        }

        return response()->json(['data' => $this->toDetail($application)]);
    }

    // Guarda Empleo e Ingresos (física), Uso de Propiedad (comercial) y Referencias (según tipo de persona)
    public function update(Request $request, int $id)
    {
        $application = $this->viewableApplication($request, $id);
        if (! $application) {
            return response()->json(['message' => 'Solicitud no encontrada.'], 404);
        }

        $rules = [];

        if ($application->tipo_persona === 'fisica') {
            $rules = array_merge($rules, [
                'profesion_oficio_puesto' => 'required|string|max:255',
                'tipo_empleo' => 'required|string|in:Dueño de negocio,Empresario,Independiente,Empleado,Comisionista,Jubilado',
                'telefono_empleo' => 'required|string|max:20',
                'extension_empleo' => 'nullable|string|max:20',
                'empresa_trabaja' => 'required|string|max:255',
                'calle_empleo' => 'required|string|max:255',
                'numero_exterior_empleo' => 'required|string|max:50',
                'numero_interior_empleo' => 'nullable|string|max:50',
                'codigo_postal_empleo' => 'required|string|max:5',
                'colonia_empleo' => 'required|string|max:255',
                'delegacion_municipio_empleo' => 'required|string|max:255',
                'estado_empleo' => 'required|string|max:255',
                'fecha_ingreso' => 'required|date',

                'jefe_nombres' => 'required|string|max:255',
                'jefe_primer_apellido' => 'required|string|max:255',
                'jefe_segundo_apellido' => 'nullable|string|max:255',
                'jefe_telefono' => 'required|string|max:20',
                'jefe_extension' => 'nullable|string|max:20',

                'ingreso_mensual_comprobable' => 'required|numeric|min:0',
                'ingreso_mensual_no_comprobable' => 'nullable|numeric|min:0',
                'numero_personas_dependen' => 'required|integer|min:0',
                'otra_persona_aporta' => 'required|boolean',
                'numero_personas_aportan' => 'required_if:otra_persona_aporta,1|nullable|integer|min:1',
                'persona_aporta_nombres' => 'required_if:otra_persona_aporta,1|nullable|string|max:255',
                'persona_aporta_primer_apellido' => 'required_if:otra_persona_aporta,1|nullable|string|max:255',
                'persona_aporta_segundo_apellido' => 'nullable|string|max:255',
                'persona_aporta_parentesco' => 'required_if:otra_persona_aporta,1|nullable|string|max:255',
                'persona_aporta_telefono' => 'required_if:otra_persona_aporta,1|nullable|string|max:20',
                'persona_aporta_empresa' => 'required_if:otra_persona_aporta,1|nullable|string|max:255',
                'persona_aporta_ingreso_comprobable' => 'required_if:otra_persona_aporta,1|nullable|numeric|min:0',

                // Referencias Personales (extra: no existe en /admin/applications, se agregó a pedido del negocio)
                // Opcionales a propósito (2026-07-07): no bloquean el guardado, el usuario las va llenando poco a poco
                'referencia_personal1_nombres' => 'nullable|string|max:255',
                'referencia_personal1_telefono' => 'nullable|string|max:20',
                'referencia_personal1_relacion' => 'nullable|string|max:255',
                'referencia_personal1_correo' => 'nullable|email|max:255',
                'referencia_personal2_nombres' => 'nullable|string|max:255',
                'referencia_personal2_telefono' => 'nullable|string|max:20',
                'referencia_personal2_relacion' => 'nullable|string|max:255',
                'referencia_personal2_correo' => 'nullable|email|max:255',
            ]);
        }

        if ($application->tipo_persona === 'moral') {
            $rules = array_merge($rules, [
                'referencia_comercial1_empresa' => 'required|string|max:255',
                'referencia_comercial1_contacto' => 'required|string|max:255',
                'referencia_comercial1_telefono' => 'required|string|max:20',
                'referencia_comercial1_correo' => 'nullable|email|max:255',
                'referencia_comercial2_empresa' => 'required|string|max:255',
                'referencia_comercial2_contacto' => 'required|string|max:255',
                'referencia_comercial2_telefono' => 'required|string|max:20',
                'referencia_comercial2_correo' => 'nullable|email|max:255',
                'referencia_comercial3_empresa' => 'required|string|max:255',
                'referencia_comercial3_contacto' => 'required|string|max:255',
                'referencia_comercial3_telefono' => 'required|string|max:20',
                'referencia_comercial3_correo' => 'nullable|email|max:255',
            ]);
        }

        if ($application->tipo_inmueble === 'comercial') {
            $rules = array_merge($rules, [
                'tipo_inmueble_desea' => 'required|string|in:Local,Oficina,Consultorio,Bodega,Nave Industrial',
                'giro_negocio' => 'required|string|max:255',
                'experiencia_giro' => 'required|string',
                'propositos_arrendamiento' => 'required|string',
                'sustituye_otro_domicilio' => 'required|boolean',
                'domicilio_anterior_calle' => 'required_if:sustituye_otro_domicilio,1|nullable|string|max:255',
                'domicilio_anterior_numero_exterior' => 'required_if:sustituye_otro_domicilio,1|nullable|string|max:50',
                'domicilio_anterior_numero_interior' => 'nullable|string|max:50',
                'domicilio_anterior_codigo_postal' => 'required_if:sustituye_otro_domicilio,1|nullable|string|max:5',
                'domicilio_anterior_colonia' => 'required_if:sustituye_otro_domicilio,1|nullable|string|max:255',
                'domicilio_anterior_delegacion_municipio' => 'required_if:sustituye_otro_domicilio,1|nullable|string|max:255',
                'domicilio_anterior_estado' => 'required_if:sustituye_otro_domicilio,1|nullable|string|max:255',
                'motivo_cambio_domicilio' => 'required_if:sustituye_otro_domicilio,1|nullable|string',
            ]);
        }

        $data = $request->validate($rules);

        $application->update($data);

        $documentos = $request->input('documentos', []);
        if (is_array($documentos)) {
            $this->procesarDocumentos($application, $documentos, $request->user());
        }

        return response()->json(['data' => $this->toDetail($application->fresh('documents'))]);
    }

    // Guarda los documentos embebidos en update()
    private function procesarDocumentos(Application $application, array $documentos, $user): void
    {
        $tagsValidos = array_keys($application->tipo_persona === 'moral'
            ? ApplicationDocument::tiposPersonaMoral()
            : ApplicationDocument::tiposPersonaFisica());

        foreach ($documentos as $doc) {
            $tag = $doc['tag'] ?? null;
            $base64 = $doc['base64'] ?? null;
            $mime = $doc['mime'] ?? null;

            if (! in_array($tag, $tagsValidos, true) || empty($base64) || empty($mime)) {
                continue;
            }

            $content = base64_decode(preg_replace('/^data:[^;]+;base64,/', '', $base64));
            if ($content === false || ! $this->validateDocumentContent($content, $mime)) {
                continue;
            }
            if (strlen($content) > 10 * 1024 * 1024) {
                continue;
            }

            $ext = match ($mime) {
                'application/pdf' => 'pdf',
                'image/png' => 'png',
                'image/webp' => 'webp',
                default => 'jpg',
            };
            $path = "applications/{$application->id}/documents/{$tag}/" . uniqid() . ".{$ext}";
            Storage::disk('spaces')->put($path, $content);

            ApplicationDocument::where('application_id', $application->id)
                ->where('tag', $tag)
                ->delete();

            ApplicationDocument::create([
                'application_id' => $application->id,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'tag' => $tag,
                'path_file' => $path,
                'mime' => $mime,
            ]);
        }
    }

    // Verifica magic bytes para asegurar que el contenido coincide con el MIME declarado
    private function validateDocumentContent(string $content, string $mime): bool
    {
        if (strlen($content) < 8) {
            return false;
        }
        $header = substr($content, 0, 8);
        return match ($mime) {
            'image/jpeg' => str_starts_with($header, "\xFF\xD8\xFF"),
            'image/png' => str_starts_with($header, "\x89PNG\r\n\x1a\n"),
            'image/webp' => str_starts_with($header, 'RIFF') && substr($content, 8, 4) === 'WEBP',
            'application/pdf' => str_starts_with($header, '%PDF'),
            default => false,
        };
    }

    // Resuelve la solicitud solo si pertenece al usuario autenticado
    private function viewableApplication(Request $request, int $id): ?Application
    {
        return Application::with('documents')
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();
    }

    // Datos mínimos para una tarjeta del listado (no el detalle completo del formulario)
    private function toListItem(Application $application): array
    {
        return [
            'id' => $application->id,
            'tipo_inmueble' => $application->tipo_inmueble,
            'estatus' => $application->estatus,
            'avance' => $this->avancePorCompletitud($application),
            'activa' => ! in_array($application->estatus, ['rechazada', 'vencida']),
            'fecha' => $application->created_at->format('d/m/Y'),
        ];
    }

    // % de campos + documentos requeridos ya llenados, sobre el total que aplica según tipo_persona/tipo_inmueble
    private function avancePorCompletitud(Application $application): int
    {
        if (in_array($application->estatus, ['aprobada', 'rechazada', 'vencida'], true)) {
            return 100;
        }

        $campos = $this->camposAplicables($application);
        $tagsDocumentos = $this->tagsDocumentosRequeridos($application);

        $total = count($campos) + count($tagsDocumentos);
        if ($total === 0) {
            return 100;
        }

        $completados = 0;
        foreach ($campos as $campo) {
            if (! is_null($application->{$campo}) && $application->{$campo} !== '') {
                $completados++;
            }
        }

        $tagsSubidos = $application->documents->pluck('tag')->all();
        foreach ($tagsDocumentos as $tag) {
            if (in_array($tag, $tagsSubidos, true)) {
                $completados++;
            }
        }

        return (int) round($completados / $total * 100);
    }

    // Campos obligatorios que aplican a esta solicitud, espejo de las reglas condicionales de update()
    private function camposAplicables(Application $application): array
    {
        $campos = [];

        if ($application->tipo_persona === 'fisica') {
            $campos = array_merge($campos, [
                'profesion_oficio_puesto', 'tipo_empleo', 'telefono_empleo', 'empresa_trabaja',
                'calle_empleo', 'numero_exterior_empleo', 'codigo_postal_empleo', 'colonia_empleo',
                'delegacion_municipio_empleo', 'estado_empleo', 'fecha_ingreso',
                'jefe_nombres', 'jefe_primer_apellido', 'jefe_telefono',
                'ingreso_mensual_comprobable', 'numero_personas_dependen', 'otra_persona_aporta',
            ]);

            if ($application->otra_persona_aporta) {
                $campos = array_merge($campos, [
                    'numero_personas_aportan', 'persona_aporta_nombres', 'persona_aporta_primer_apellido',
                    'persona_aporta_parentesco', 'persona_aporta_telefono', 'persona_aporta_empresa',
                    'persona_aporta_ingreso_comprobable',
                ]);
            }

            foreach ([1, 2] as $n) {
                $campos = array_merge($campos, [
                    "referencia_personal{$n}_nombres", "referencia_personal{$n}_telefono", "referencia_personal{$n}_relacion",
                ]);
            }
        }

        if ($application->tipo_persona === 'moral') {
            foreach ([1, 2, 3] as $n) {
                $campos = array_merge($campos, [
                    "referencia_comercial{$n}_empresa", "referencia_comercial{$n}_contacto", "referencia_comercial{$n}_telefono",
                ]);
            }
        }

        if ($application->tipo_inmueble === 'comercial') {
            $campos = array_merge($campos, [
                'tipo_inmueble_desea', 'giro_negocio', 'experiencia_giro', 'propositos_arrendamiento', 'sustituye_otro_domicilio',
            ]);

            if ($application->sustituye_otro_domicilio) {
                $campos = array_merge($campos, [
                    'domicilio_anterior_calle', 'domicilio_anterior_numero_exterior', 'domicilio_anterior_codigo_postal',
                    'domicilio_anterior_colonia', 'domicilio_anterior_delegacion_municipio', 'domicilio_anterior_estado',
                    'motivo_cambio_domicilio',
                ]);
            }
        }

        return $campos;
    }

    // Tags de documentos requeridos según tipo_persona (excluye "otro", que es libre/opcional)
    private function tagsDocumentosRequeridos(Application $application): array
    {
        $tipos = $application->tipo_persona === 'moral'
            ? ApplicationDocument::tiposPersonaMoral()
            : ApplicationDocument::tiposPersonaFisica();

        unset($tipos['otro']);

        return array_keys($tipos);
    }

    private function toDetail(Application $application): array
    {
        $documents = $application->relationLoaded('documents') ? $application->documents : $application->documents()->get();

        return array_merge($application->only([
            'id', 'folio', 'estatus', 'tipo_persona', 'tipo_inmueble',
            'profesion_oficio_puesto', 'tipo_empleo', 'telefono_empleo', 'extension_empleo', 'empresa_trabaja',
            'calle_empleo', 'numero_exterior_empleo', 'numero_interior_empleo', 'codigo_postal_empleo',
            'colonia_empleo', 'delegacion_municipio_empleo', 'estado_empleo', 'fecha_ingreso',
            'jefe_nombres', 'jefe_primer_apellido', 'jefe_segundo_apellido', 'jefe_telefono', 'jefe_extension',
            'ingreso_mensual_comprobable', 'ingreso_mensual_no_comprobable', 'numero_personas_dependen',
            'otra_persona_aporta', 'numero_personas_aportan', 'persona_aporta_nombres',
            'persona_aporta_primer_apellido', 'persona_aporta_segundo_apellido', 'persona_aporta_parentesco',
            'persona_aporta_telefono', 'persona_aporta_empresa', 'persona_aporta_ingreso_comprobable',
            'tipo_inmueble_desea', 'giro_negocio', 'experiencia_giro', 'propositos_arrendamiento',
            'sustituye_otro_domicilio', 'domicilio_anterior_calle', 'domicilio_anterior_numero_exterior',
            'domicilio_anterior_numero_interior', 'domicilio_anterior_codigo_postal', 'domicilio_anterior_colonia',
            'domicilio_anterior_delegacion_municipio', 'domicilio_anterior_estado', 'motivo_cambio_domicilio',
            'referencia_comercial1_empresa', 'referencia_comercial1_contacto', 'referencia_comercial1_telefono', 'referencia_comercial1_correo',
            'referencia_comercial2_empresa', 'referencia_comercial2_contacto', 'referencia_comercial2_telefono', 'referencia_comercial2_correo',
            'referencia_comercial3_empresa', 'referencia_comercial3_contacto', 'referencia_comercial3_telefono', 'referencia_comercial3_correo',
            'referencia_personal1_nombres', 'referencia_personal1_telefono', 'referencia_personal1_relacion', 'referencia_personal1_correo',
            'referencia_personal2_nombres', 'referencia_personal2_telefono', 'referencia_personal2_relacion', 'referencia_personal2_correo',
        ]), [
            // only() no respeta el formato del cast ('date:Y-m-d'), solo toArray() lo hace — se fuerza aquí
            // para que <input type="date"> del frontend reciba "Y-m-d" y no el ISO8601 con hora que da Carbon por default
            'fecha_ingreso' => $application->fecha_ingreso?->format('Y-m-d'),
            'documents' => $documents->map(fn ($doc) => [
                'id' => $doc->id,
                'tag' => $doc->tag,
                'mime' => $doc->mime,
                'url' => Storage::disk('spaces')->url($doc->path_file),
            ])->values(),
        ]);
    }
}
