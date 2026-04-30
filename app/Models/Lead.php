<?php

namespace App\Models;

use App\Enums\LeadCanal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\WhatsappMessage;

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

    /**
     * Variantes del número para comparar mensajes entrantes/salientes.
     *
     * @return array<int, string>
     */
    public function whatsappPhoneCandidates(): array
    {
        $normalized = $this->normalizedWhatsappForEvolution();
        if (! $normalized) {
            return [];
        }

        $digits = preg_replace('/\D/', '', $normalized);
        if (! $digits) {
            return [];
        }

        $candidates = [$digits];

        // México: aceptar tanto 52XXXXXXXXXX como 521XXXXXXXXXX.
        if (str_starts_with($digits, '521') && strlen($digits) >= 13) {
            $withoutMobilePrefix = '52'.substr($digits, 3);
            $candidates[] = $withoutMobilePrefix;
        }

        if (str_starts_with($digits, '52') && ! str_starts_with($digits, '521') && strlen($digits) >= 12) {
            $withMobilePrefix = '521'.substr($digits, 2);
            $candidates[] = $withMobilePrefix;
        }

        // Permitir formatos con + para registros previos.
        $plusCandidates = array_map(fn (string $number): string => '+'.$number, $candidates);

        return array_values(array_unique(array_merge($candidates, $plusCandidates)));
    }

    public function whatsappMessages(): HasMany
    {
        return $this->hasMany(WhatsappMessage::class, 'lead_id');
    }

    /**
     * Conversación completa: mensajes vinculados por lead_id O por teléfono
     * (para mensajes entrantes que llegaron antes de vincularse).
     */
    public function whatsappConversation()
    {
        $leadId = $this->id;
        $candidates = $this->whatsappPhoneCandidates();

        return WhatsappMessage::query()
            ->where(function ($q) use ($leadId, $candidates) {
                $q->where('lead_id', $leadId);
                if (! empty($candidates)) {
                    $q->orWhereIn('phone', $candidates);
                }
            })
            ->orderBy('created_at');
    }
}
