<?php

namespace App\Services;

use App\Filament\Resources\OwnerResource;
use App\Filament\Resources\SaleResource;
use App\Filament\Resources\TenantResource;
use App\Models\Lead;
use App\Models\Owner;
use App\Models\Sale;
use App\Models\Tenant;

class LeadProspectProcessor
{
    /**
     * El prospecto ya fue convertido (registro final creado).
     */
    public function hasBeenProcessed(Lead $lead): bool
    {
        return $lead->etapa === 'ganado';
    }

    /**
     * Puede convertirse automáticamente al guardar el formulario.
     */
    public function shouldProcessOnSave(Lead $lead): bool
    {
        if ($this->hasBeenProcessed($lead)) {
            return false;
        }

        return in_array($lead->tipo_cliente, ['inquilino', 'arrendador', 'comprador', 'vendedor'], true);
    }

    /**
     * @return array{redirect: ?string, notification_title: ?string, notification_warning: bool}
     */
    public function process(Lead $lead): array
    {
        ['nombres' => $nombres, 'paterno' => $paterno, 'materno' => $materno] = $this->parseNombre($lead->nombre);

        if ($lead->tipo_cliente === 'inquilino') {
            $tenant = Tenant::where('email', $lead->correo ?? '')
                ->where('nombres', $nombres)
                ->where('primer_apellido', $paterno)
                ->first();

            if (! $tenant) {
                $tenant = Tenant::create([
                    'tipo_persona' => 'fisica',
                    'nombres' => $nombres,
                    'primer_apellido' => $paterno,
                    'segundo_apellido' => $materno,
                    'email' => $lead->correo ?? '',
                    'telefono_celular' => $lead->telefono ?? '',
                    'historial_acciones' => $lead->historial_acciones,
                    'estado_civil' => 'soltero',
                    'nacionalidad' => 'mexicana',
                    'sexo' => 'masculino',
                    'tipo_identificacion' => 'INE',
                    'asesor_id' => $lead->responsable_id,
                ]);
            }

            $lead->update(['etapa' => 'ganado']);

            return [
                'redirect' => TenantResource::getUrl('edit', ['record' => $tenant]),
                'notification_title' => 'Inquilino creado',
                'notification_warning' => false,
            ];
        }

        if ($lead->tipo_cliente === 'arrendador') {
            $owner = Owner::where('email', $lead->correo ?? '')
                ->where('nombres', $nombres)
                ->where('primer_apellido', $paterno)
                ->first();

            if (! $owner) {
                $owner = Owner::create([
                    'tipo_persona' => 'fisica',
                    'nombres' => $nombres,
                    'primer_apellido' => $paterno,
                    'segundo_apellido' => $materno,
                    'email' => $lead->correo ?? '',
                    'telefono' => $lead->telefono ?? '',
                    'historial_acciones' => $lead->historial_acciones,
                    'estado_civil' => 'Soltero',
                    'sexo' => 'Masculino',
                    'nacionalidad' => 'Mexicana',
                    'tipo_identificacion' => 'INE',
                    'asesor_id' => $lead->responsable_id,
                ]);
            }

            $lead->update(['etapa' => 'ganado']);

            return [
                'redirect' => OwnerResource::getUrl('edit', ['record' => $owner]),
                'notification_title' => 'Arrendador creado',
                'notification_warning' => false,
            ];
        }

        if (in_array($lead->tipo_cliente, ['comprador', 'vendedor'], true)) {
            $data = [
                'estatus_operacion' => 'Activa',
            ];

            if ($lead->tipo_cliente === 'comprador') {
                $data['nombre_cliente_principal'] = $lead->nombre;
                $data['comprador_nombres'] = $nombres;
                $data['comprador_ap_paterno'] = $paterno;
                $data['comprador_ap_materno'] = $materno;
                $data['comprador_email'] = $lead->correo;
                $data['comprador_celular'] = $lead->telefono;

                $sale = Sale::where('comprador_email', $lead->correo ?? '')
                    ->where('comprador_nombres', $nombres)
                    ->first();
            } else {
                $data['nombre_cliente_principal'] = $lead->nombre;
                $data['vendedor_nombres'] = $nombres;
                $data['vendedor_ap_paterno'] = $paterno;
                $data['vendedor_ap_materno'] = $materno;
                $data['vendedor_email'] = $lead->correo;
                $data['vendedor_celular'] = $lead->telefono;

                $sale = Sale::where('vendedor_email', $lead->correo ?? '')
                    ->where('vendedor_nombres', $nombres)
                    ->first();
            }

            if (! $sale) {
                $sale = Sale::create($data);
            }

            $lead->update(['etapa' => 'ganado']);

            return [
                'redirect' => SaleResource::getUrl('edit', ['record' => $sale]),
                'notification_title' => 'Venta creada ('.ucfirst($lead->tipo_cliente).')',
                'notification_warning' => false,
            ];
        }

        return [
            'redirect' => null,
            'notification_title' => 'Tipo de cliente ('.$lead->tipo_cliente.') no es convertible automáticamente',
            'notification_warning' => true,
        ];
    }

    /**
     * @return array{nombres: string, paterno: string, materno: string}
     */
    public function parseNombre(?string $nombreCompleto): array
    {
        $partes = explode(' ', trim((string) $nombreCompleto));

        if (count($partes) === 1) {
            return ['nombres' => $partes[0], 'paterno' => '', 'materno' => ''];
        }

        if (count($partes) === 2) {
            return ['nombres' => $partes[0], 'paterno' => $partes[1], 'materno' => ''];
        }

        $materno = array_pop($partes);
        $paterno = array_pop($partes);
        $nombres = implode(' ', $partes);

        return ['nombres' => $nombres, 'paterno' => $paterno, 'materno' => $materno];
    }
}
