<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Tenant;
use App\Models\TenantRequest;
use Illuminate\Http\Request;

class TenantRequestController extends Controller
{
    // Ya capturados en Application@update; se copian para no pedirlos dos veces
    private const CAMPOS_EMPLEO_DESDE_APPLICATION = [
        'profesion_oficio_puesto', 'tipo_empleo', 'telefono_empleo', 'extension_empleo', 'empresa_trabaja',
        'calle_empleo', 'numero_exterior_empleo', 'numero_interior_empleo', 'codigo_postal_empleo',
        'colonia_empleo', 'delegacion_municipio_empleo', 'estado_empleo', 'fecha_ingreso',
        'jefe_nombres', 'jefe_primer_apellido', 'jefe_segundo_apellido', 'jefe_telefono', 'jefe_extension',
        'ingreso_mensual_comprobable', 'ingreso_mensual_no_comprobable', 'numero_personas_dependen',
        'otra_persona_aporta', 'numero_personas_aportan', 'persona_aporta_nombres',
        'persona_aporta_primer_apellido', 'persona_aporta_segundo_apellido', 'persona_aporta_parentesco',
        'persona_aporta_telefono', 'persona_aporta_empresa', 'persona_aporta_ingreso_comprobable',
    ];

    // Detalle actual (o valores vacíos si aún no existe) para prefill del formulario
    public function show(Request $request, int $applicationId)
    {
        $application = $this->viewableApplication($request, $applicationId);
        if (! $application) {
            return response()->json(['message' => 'Solicitud no encontrada.'], 404);
        }

        return response()->json(['data' => $this->toDetail($application->tenantRequest)]);
    }

    // Crea o actualiza (upsert) la solicitud residencial completa ligada a la Application
    public function update(Request $request, int $applicationId)
    {
        $application = $this->viewableApplication($request, $applicationId);
        if (! $application) {
            return response()->json(['message' => 'Solicitud no encontrada.'], 404);
        }

        $tenant = Tenant::where('user_id', $request->user()->id)->first();
        if (! $tenant) {
            return response()->json(['message' => 'No se encontró el perfil de inquilino.'], 422);
        }

        $rules = $application->tipo_inmueble === 'comercial' ? $this->reglasComercial() : $this->reglasResidencial();
        $data = $request->validate($rules);

        $empleoDesdeApplication = $application->only(self::CAMPOS_EMPLEO_DESDE_APPLICATION);

        $tenantRequest = TenantRequest::firstOrNew(['application_id' => $application->id]);
        $tenantRequest->fill(array_merge($empleoDesdeApplication, $data, [
            'tenant_id' => $tenant->id,
            'application_id' => $application->id,
            'tipo_persona' => 'fisica',
            'estatus' => $tenantRequest->exists ? $tenantRequest->estatus : 'nueva',
        ]));
        $tenantRequest->save();

        return response()->json(['data' => $this->toDetail($tenantRequest)]);
    }

    private function reglasResidencial(): array
    {
        return [
            'tipo_inmueble' => 'required|string|in:Casa,Departamento,Terreno,Villa',
            'uso_suelo' => 'required|string|in:Habitacional,Comercial,Industrial',
            'precio_renta' => 'required|numeric|min:0',
            'plazo_desde' => 'required|date',
            'plazo_hasta' => 'required|date|after_or_equal:plazo_desde',

            'inmueble_calle' => 'required|string|max:255',
            'inmueble_numero_exterior' => 'required|string|max:50',
            'inmueble_numero_interior' => 'nullable|string|max:50',
            'inmueble_codigo_postal' => 'required|string|max:5',
            'inmueble_colonia' => 'required|string|max:255',
            'inmueble_delegacion_municipio' => 'required|string|max:255',
            'inmueble_estado' => 'required|string|max:255',

            'numero_adultos' => 'required|integer|min:1',
            'nombre_adulto_1' => 'nullable|string|max:255',
            'nombre_adulto_2' => 'nullable|string|max:255',

            'tiene_mascotas' => 'required|boolean',
            'especificar_mascotas' => 'required_if:tiene_mascotas,1|nullable|string|max:255',

            'referencia_familiar1_nombres' => 'required|string|max:255',
            'referencia_familiar1_telefono' => 'required|string|max:20',
            'referencia_familiar1_email' => 'nullable|email|max:255',
            'referencia_familiar1_relacion' => 'required|string|max:255',

            'referencia_familiar2_nombres' => 'required|string|max:255',
            'referencia_familiar2_telefono' => 'required|string|max:20',
            'referencia_familiar2_email' => 'nullable|email|max:255',
            'referencia_familiar2_relacion' => 'required|string|max:255',

            'referencia_personal1_nombres' => 'required|string|max:255',
            'referencia_personal1_telefono' => 'required|string|max:20',
            'referencia_personal1_email' => 'nullable|email|max:255',
            'referencia_personal1_relacion' => 'required|string|max:255',

            'referencia_personal2_nombres' => 'required|string|max:255',
            'referencia_personal2_telefono' => 'required|string|max:20',
            'referencia_personal2_email' => 'nullable|email|max:255',
            'referencia_personal2_relacion' => 'required|string|max:255',
        ];
    }

    private function reglasComercial(): array
    {
        return [
            'tipo_inmueble' => 'required|string|in:Oficina,Local comercial,Edificio,Terreno,Nave industrial,Consultorio',
            'giro_negocio' => 'required|string|max:255',
            'experiencia_giro' => 'required|string|max:1000',
            'precio_renta' => 'required|numeric|min:0',
            'plazo_desde' => 'required|date',
            'plazo_hasta' => 'required|date|after_or_equal:plazo_desde',

            'inmueble_calle' => 'required|string|max:255',
            'inmueble_numero_exterior' => 'required|string|max:50',
            'inmueble_numero_interior' => 'nullable|string|max:50',
            'inmueble_codigo_postal' => 'required|string|max:5',
            'inmueble_colonia' => 'required|string|max:255',
            'inmueble_delegacion_municipio' => 'required|string|max:255',
            'inmueble_estado' => 'required|string|max:255',

            // Puesto/empresa/fecha/ingreso ya se copian desde Application, no se piden aquí
            'ocupacion_actual' => 'required|string|max:255',
            'numero_empleados' => 'required|integer|min:0',
            'dominio_internet' => 'nullable|string|max:255',

            'referencia_comercial1_contacto' => 'required|string|max:255',
            'referencia_comercial1_puesto' => 'required|string|max:255',
            'referencia_comercial1_empresa' => 'required|string|max:255',
            'referencia_comercial1_telefono' => 'required|string|max:20',
            'referencia_comercial1_correo' => 'nullable|email|max:255',
            'referencia_comercial1_tiempo_conocerlo' => 'required|string|max:255',

            'referencia_comercial2_contacto' => 'required|string|max:255',
            'referencia_comercial2_puesto' => 'required|string|max:255',
            'referencia_comercial2_empresa' => 'required|string|max:255',
            'referencia_comercial2_telefono' => 'required|string|max:20',
            'referencia_comercial2_correo' => 'nullable|email|max:255',
            'referencia_comercial2_tiempo_conocerlo' => 'required|string|max:255',

            'referencia_comercial3_contacto' => 'required|string|max:255',
            'referencia_comercial3_puesto' => 'required|string|max:255',
            'referencia_comercial3_empresa' => 'required|string|max:255',
            'referencia_comercial3_telefono' => 'required|string|max:20',
            'referencia_comercial3_correo' => 'nullable|email|max:255',
            'referencia_comercial3_tiempo_conocerlo' => 'required|string|max:255',
        ];
    }

    private function viewableApplication(Request $request, int $id): ?Application
    {
        return Application::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();
    }

    private function toDetail(?TenantRequest $tenantRequest): ?array
    {
        if (! $tenantRequest) {
            return null;
        }

        return $tenantRequest->only([
            'id', 'estatus',
            'tipo_inmueble', 'uso_suelo', 'precio_renta', 'plazo_desde', 'plazo_hasta',
            'inmueble_calle', 'inmueble_numero_exterior', 'inmueble_numero_interior', 'inmueble_codigo_postal',
            'inmueble_colonia', 'inmueble_delegacion_municipio', 'inmueble_estado',
            'numero_adultos', 'nombre_adulto_1', 'nombre_adulto_2',
            'tiene_mascotas', 'especificar_mascotas',
            'referencia_familiar1_nombres', 'referencia_familiar1_telefono', 'referencia_familiar1_email', 'referencia_familiar1_relacion',
            'referencia_familiar2_nombres', 'referencia_familiar2_telefono', 'referencia_familiar2_email', 'referencia_familiar2_relacion',
            'referencia_personal1_nombres', 'referencia_personal1_telefono', 'referencia_personal1_email', 'referencia_personal1_relacion',
            'referencia_personal2_nombres', 'referencia_personal2_telefono', 'referencia_personal2_email', 'referencia_personal2_relacion',
            'giro_negocio', 'experiencia_giro', 'ocupacion_actual', 'empresa_trabaja', 'numero_empleados', 'dominio_internet',
            'profesion_oficio_puesto', 'fecha_ingreso', 'ingreso_mensual_comprobable',
            'referencia_comercial1_contacto', 'referencia_comercial1_puesto', 'referencia_comercial1_empresa', 'referencia_comercial1_telefono', 'referencia_comercial1_correo', 'referencia_comercial1_tiempo_conocerlo',
            'referencia_comercial2_contacto', 'referencia_comercial2_puesto', 'referencia_comercial2_empresa', 'referencia_comercial2_telefono', 'referencia_comercial2_correo', 'referencia_comercial2_tiempo_conocerlo',
            'referencia_comercial3_contacto', 'referencia_comercial3_puesto', 'referencia_comercial3_empresa', 'referencia_comercial3_telefono', 'referencia_comercial3_correo', 'referencia_comercial3_tiempo_conocerlo',
        ]);
    }
}
