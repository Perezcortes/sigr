<?php

namespace App\Models;

use App\Enums\LeadCanal;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    protected $table = 'leads';

    protected $fillable = [
        'nombre',
        'correo',
        'telefono',
        'origen',
        'canal',
        'tipo_cliente',
        'calificacion_lead',
        'mensaje',
        'url_propiedad',
        'metros_cuadrados',
        'numero_recamaras',
        'presupuesto',
        'localidades',
        'comentarios',
        'historial_acciones',
        'etapa',
        'responsable_id',
        'payload_original',
    ];

    protected $casts = [
        'canal' => LeadCanal::class,
        'payload_original' => 'array',
        'historial_acciones' => 'array',
    ];

    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    /**
     * Automatización:
     * Se ejecuta automáticamente cada vez que se guarda o actualiza el registro.
     */
    protected static function booted()
    {
        static::saved(function ($lead) {

            // Evaluamos si el lead está calificado
            if (in_array($lead->calificacion_lead, ['perfilado', 'potencial'])) {

                // LÓGICA DE SEPARACIÓN DE NOMBRES
                $nombreCompleto = trim($lead->nombre);
                $partes = explode(' ', $nombreCompleto);
                $cantidad = count($partes);

                $nombres = $nombreCompleto;
                $primer_apellido = '';
                $segundo_apellido = '';

                if ($cantidad == 2) {
                    $nombres = $partes[0];
                    $primer_apellido = $partes[1];
                } elseif ($cantidad == 3) {
                    $nombres = $partes[0];
                    $primer_apellido = $partes[1];
                    $segundo_apellido = $partes[2];
                } elseif ($cantidad >= 4) {
                    $segundo_apellido = array_pop($partes);
                    $primer_apellido = array_pop($partes);
                    $nombres = implode(' ', $partes);
                }

                // Datos base compartidos
                $datosBase = [
                    'nombres' => $nombres,
                    'primer_apellido' => $primer_apellido,
                    'segundo_apellido' => $segundo_apellido,
                    'email' => $lead->correo,
                    'asesor_id' => $lead->responsable_id,
                    'tipo_persona' => 'fisica',
                    'historial_acciones' => $lead->historial_acciones,
                ];

                // Obtenemos el correo original
                $correoBusqueda = $lead->getOriginal('correo') ?? $lead->correo;

                // Sincronizar Inquilino
                if ($lead->tipo_cliente === 'inquilino') {
                    $datosTenant = $datosBase;
                    $datosTenant['telefono_celular'] = $lead->telefono;

                    Tenant::updateOrCreate(
                        ['email' => $correoBusqueda],
                        $datosTenant
                    );
                }
                // Sincronizar Arrendador / Vendedor (Owner)
                elseif (in_array($lead->tipo_cliente, ['arrendador', 'vendedor'])) {
                    $datosOwner = $datosBase;
                    $datosOwner['telefono'] = $lead->telefono;

                    Owner::updateOrCreate(
                        ['email' => $correoBusqueda],
                        $datosOwner
                    );
                }
            }
        });
    }

    /**
     * Teléfono en formato internacional para Evolution API (México: 521 + 10 dígitos).
     */
    public function normalizedWhatsappForEvolution(): ?string
    {
        if (empty($this->telefono)) {
            return null;
        }

        $d = preg_replace('/\D/', '', (string) $this->telefono);
        if ($d === '') {
            return null;
        }

        if (str_starts_with($d, '521') && strlen($d) >= 12) {
            return $d;
        }

        if (str_starts_with($d, '52') && strlen($d) >= 12) {
            return $d;
        }

        if (strlen($d) === 10) {
            return '521'.$d;
        }

        return $d;
    }
}
