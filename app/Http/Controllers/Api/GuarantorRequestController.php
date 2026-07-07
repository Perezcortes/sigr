<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\GuarantorRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GuarantorRequestController extends Controller
{
    // Detalle actual (o null si el inquilino aún no agregó Fiador)
    public function show(Request $request, int $applicationId)
    {
        $application = $this->viewableApplication($request, $applicationId);
        if (! $application) {
            return response()->json(['message' => 'Solicitud no encontrada.'], 404);
        }

        return response()->json(['data' => $this->toDetail($application->guarantorRequest)]);
    }

    // Upsert de Fiador y/o Garantía; cada bloque solo es obligatorio si su propia bandera viene en true
    public function update(Request $request, int $applicationId)
    {
        $application = $this->viewableApplication($request, $applicationId);
        if (! $application) {
            return response()->json(['message' => 'Solicitud no encontrada.'], 404);
        }

        $validated = $request->validate([
            'agregar_fiador' => 'required|boolean',
            'agregar_garantia' => 'required|boolean',

            'tipo_figura' => 'required_if:agregar_fiador,1|nullable|string|in:Fiador,Obligado solidario',
            'nombres' => 'required_if:agregar_fiador,1|nullable|string|max:100',
            'primer_apellido' => 'required_if:agregar_fiador,1|nullable|string|max:80',
            'segundo_apellido' => 'nullable|string|max:80',
            'curp' => 'required_if:agregar_fiador,1|nullable|string|max:18',
            'relacion_solicitante' => 'required_if:agregar_fiador,1|nullable|string|max:80',

            'empresa_trabaja' => 'required_if:agregar_fiador,1|nullable|string|max:100',
            'pagina_internet' => 'nullable|string|max:255',
            'profesion_puesto' => 'required_if:agregar_fiador,1|nullable|string|max:100',
            'fecha_ingreso_empleo' => 'required_if:agregar_fiador,1|nullable|date',
            'ingreso_mensual' => 'required_if:agregar_fiador,1|nullable|numeric|min:0',
            'actividad_economica' => 'nullable|string|max:1000',

            'garantia_tipo_propiedad' => 'required_if:agregar_garantia,1|nullable|string|in:Residencial,Comercial,Mixta',
            'garantia_calle' => 'required_if:agregar_garantia,1|nullable|string|max:100',
            'garantia_numero_exterior' => 'required_if:agregar_garantia,1|nullable|string|max:20',
            'garantia_numero_interior' => 'nullable|string|max:20',
            'garantia_codigo_postal' => 'required_if:agregar_garantia,1|nullable|string|max:10',
            'garantia_colonia' => 'required_if:agregar_garantia,1|nullable|string|max:100',
            'garantia_municipio' => 'required_if:agregar_garantia,1|nullable|string|max:100',
            'garantia_estado' => 'required_if:agregar_garantia,1|nullable|string|max:50',

            'garantia_num_escritura' => 'required_if:agregar_garantia,1|nullable|string|max:50',
            'garantia_fecha_escritura' => 'required_if:agregar_garantia,1|nullable|date',
            'garantia_notario_nombres' => 'required_if:agregar_garantia,1|nullable|string|max:100',
            'garantia_notario_paterno' => 'required_if:agregar_garantia,1|nullable|string|max:80',
            'garantia_notario_materno' => 'nullable|string|max:80',
            'garantia_num_notaria' => 'required_if:agregar_garantia,1|nullable|string|max:20',
            'garantia_lugar_notaria' => 'required_if:agregar_garantia,1|nullable|string|max:100',
            'garantia_folio_real' => 'required_if:agregar_garantia,1|nullable|string|max:50',
            'garantia_fecha_rpp' => 'required_if:agregar_garantia,1|nullable|date',
        ]);

        // Son banderas de control, no columnas
        $data = collect($validated)->except(['agregar_fiador', 'agregar_garantia'])->all();

        $guarantorRequest = GuarantorRequest::firstOrNew(['application_id' => $application->id]);
        $guarantorRequest->fill(array_merge($data, [
            'application_id' => $application->id,
            'tipo_persona' => 'fisica',
            'estatus' => $guarantorRequest->exists ? $guarantorRequest->estatus : 'nueva',
        ]));
        $guarantorRequest->save();

        $documentos = $request->input('documentos', []);
        if (is_array($documentos)) {
            $this->procesarDocumentos($application, $documentos, $request->user());
        }

        return response()->json(['data' => $this->toDetail($guarantorRequest)]);
    }

    // Quita al Fiador de la solicitud (el inquilino desactivó "Agregar Fiador u Obligado Solidario")
    public function destroy(Request $request, int $applicationId)
    {
        $application = $this->viewableApplication($request, $applicationId);
        if (! $application) {
            return response()->json(['message' => 'Solicitud no encontrada.'], 404);
        }

        $application->guarantorRequest?->delete();

        $tagsFiador = array_keys(ApplicationDocument::tiposFiador());
        ApplicationDocument::where('application_id', $application->id)
            ->whereIn('tag', $tagsFiador)
            ->get()
            ->each(function (ApplicationDocument $doc) {
                Storage::disk('spaces')->delete($doc->path_file);
                $doc->delete();
            });

        return response()->json(['data' => null]);
    }

    // Mismo patrón base64 + magic bytes que ApplicationController, tags de tiposFiador()
    private function procesarDocumentos(Application $application, array $documentos, $user): void
    {
        $tagsValidos = array_keys(ApplicationDocument::tiposFiador());

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

    private function viewableApplication(Request $request, int $id): ?Application
    {
        return Application::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();
    }

    private function toDetail(?GuarantorRequest $guarantorRequest): ?array
    {
        if (! $guarantorRequest) {
            return null;
        }

        return $guarantorRequest->only([
            'id', 'estatus', 'tipo_figura',
            'nombres', 'primer_apellido', 'segundo_apellido', 'curp', 'relacion_solicitante',
            'empresa_trabaja', 'pagina_internet', 'profesion_puesto', 'fecha_ingreso_empleo',
            'ingreso_mensual', 'actividad_economica',
            'garantia_tipo_propiedad', 'garantia_calle', 'garantia_numero_exterior', 'garantia_numero_interior',
            'garantia_codigo_postal', 'garantia_colonia', 'garantia_municipio', 'garantia_estado',
            'garantia_num_escritura', 'garantia_fecha_escritura',
            'garantia_notario_nombres', 'garantia_notario_paterno', 'garantia_notario_materno',
            'garantia_num_notaria', 'garantia_lugar_notaria', 'garantia_folio_real', 'garantia_fecha_rpp',
        ]);
    }
}
