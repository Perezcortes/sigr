<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ApplicationController extends Controller
{
    // Crea la solicitud con lo mínimo
    public function store(Request $request)
    {
        if (! $request->user()->is_tenant) {
            return response()->json(['message' => 'Solo los inquilinos pueden crear solicitudes.'], 403);
        }

        $data = $request->validate([
            'tipo_inmueble' => 'required|string|in:residencial,comercial',
        ]);

        $application = Application::create([
            'user_id' => $request->user()->id,
            'tipo_persona' => 'fisica',
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

    // Guarda datos de Empleo e Ingresos
    public function update(Request $request, int $id)
    {
        $application = $this->viewableApplication($request, $id);
        if (! $application) {
            return response()->json(['message' => 'Solicitud no encontrada.'], 404);
        }

        $data = $request->validate([
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
        ]);

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
        $tagsValidos = array_merge(
            array_keys(ApplicationDocument::tiposPersonaFisica()),
            array_keys(ApplicationDocument::tiposComercial())
        );

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
        ]), [
            'documents' => $documents->map(fn ($doc) => [
                'id' => $doc->id,
                'tag' => $doc->tag,
                'mime' => $doc->mime,
                'url' => Storage::disk('spaces')->url($doc->path_file),
            ])->values(),
        ]);
    }
}
