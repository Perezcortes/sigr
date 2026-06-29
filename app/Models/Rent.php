<?php

namespace App\Models;

use App\Models\Traits\HasHashId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rent extends Model
{
    use HasHashId;

    protected $fillable = [
        'tipo_poliza',
        'office_id',
        'tenant_id',
        'owner_id',
        'application_id',
        'property_id',
        'asesor_id',
        'start_date',
        'end_date',
        'amount',
        'payment_frequency',
        // Datos de la renta
        'folio',
        'pdr_office_id',
        'pdr_asesor_id',
        'sucursal',
        'inmobiliaria',
        'estatus',
        'tipo_inmueble',
        'renta',
        'monto_comision',
        'porcentaje_comision_principal',
        'comisiones_divididas',
        'plazo_arrendamiento',
        'fecha_firma',
        'con_poliza',
        // Obligado solidario / fiador
        'tiene_fiador',
        'fiador_tipo_persona',
        'fiador_tipo',
        'fiador_nombres',
        'fiador_primer_apellido',
        'fiador_segundo_apellido',
        'fiador_sexo',
        'fiador_razon_social',
        'fiador_rfc',
        'fiador_email',
        // Datos de la propiedad
        'tipo_propiedad',
        'calle',
        'numero_exterior',
        'numero_interior',
        'referencias_ubicacion',
        'colonia',
        'municipio',
        'estado',
        'codigo_postal',
        'is_administrada_por_agente',
        'dia_cobro_renta',
        'enviar_recordatorio_inquilino',
        'enviar_recordatorio_propietario',
        'notas_administracion',
        'notif_recordatorios_email',
        'notif_recordatorios_push',
        'notif_recordatorios_whatsapp',
        'notif_reporte_pago_email',
        'notif_reporte_pago_push',
        'notif_reporte_pago_whatsapp',
        'notif_mensajes_email',
        'notif_mensajes_push',
        'notif_mensajes_whatsapp',
        'notif_mantenimiento_email',
        'notif_mantenimiento_push',
        'notif_mantenimiento_whatsapp',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'amount' => 'decimal:2',
        'renta' => 'decimal:2',
        'monto_comision' => 'decimal:2',
        'porcentaje_comision_principal' => 'decimal:2',
        'comisiones_divididas' => 'array',
        'fecha_firma' => 'date',
        'con_poliza' => 'boolean',
        'is_administrada_por_agente' => 'boolean',
        'enviar_recordatorio_inquilino' => 'boolean',
        'enviar_recordatorio_propietario' => 'boolean',
        'notif_recordatorios_email' => 'boolean',
        'notif_recordatorios_push' => 'boolean',
        'notif_recordatorios_whatsapp' => 'boolean',
        'notif_reporte_pago_email' => 'boolean',
        'notif_reporte_pago_push' => 'boolean',
        'notif_reporte_pago_whatsapp' => 'boolean',
        'notif_mensajes_email' => 'boolean',
        'notif_mensajes_push' => 'boolean',
        'notif_mensajes_whatsapp' => 'boolean',
        'notif_mantenimiento_email' => 'boolean',
        'notif_mantenimiento_push' => 'boolean',
        'notif_mantenimiento_whatsapp' => 'boolean',
    ];

    /**
     * Boot del modelo para generar el folio automáticamente
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($rent) {
            // Generar Folio
            if (empty($rent->folio)) {
                $rent->folio = self::generateFolio();
            }

            // Asignar al creador como Agente por defecto
            if (empty($rent->asesor_id) && auth()->check()) {
                $rent->asesor_id = auth()->id();
            }

            // Asignar la sucursal del creador
            if (empty($rent->office_id) && auth()->check()) {
                $rent->office_id = auth()->user()->office_id;
            }
        });

        // Detectar cambios y guardarlos en el historial de comentarios
        static::updated(function ($rent) {
            $cambios = $rent->getDirty();
            unset($cambios['updated_at']);

            if (count($cambios) > 0 && auth()->check()) {
                $mensaje = "El sistema registró las siguientes actualizaciones en el expediente:\n";
                $cambiosReales = 0; // Para no guardar comentarios vacíos

                // 1. DICCIONARIO
                $nombresLegibles = [
                    'office_id' => 'Sucursal',
                    'asesor_id' => 'Agente asignado',
                    'tenant_id' => 'Inquilino',
                    'owner_id' => 'Propietario',
                    'property_id' => 'Inmueble',
                    'application_id' => 'Solicitud de arrendamiento',
                    'estatus' => 'Estatus de la operación',
                    'is_administrada_por_agente' => 'Administración a cargo del agente',
                    'enviar_recordatorio_inquilino' => 'Aviso de cobro al inquilino',
                    'enviar_recordatorio_propietario' => 'Notificación al propietario',
                    'dia_cobro_renta' => 'Día de cobro mensual',
                    'renta' => 'Monto de renta',
                    'monto_comision' => 'Monto de comisión',
                ];

                // 2. FUNCIÓN TRADUCTORA: Convierte IDs y booleanos en texto real
                $formatearValor = function ($columna, $valor) {
                    if ($valor === null || $valor === '') {
                        return 'Sin asignar';
                    }

                    // Traducir los "1" y "0" a "Sí" y "No"
                    if (in_array($columna, ['is_administrada_por_agente', 'enviar_recordatorio_inquilino', 'enviar_recordatorio_propietario', 'tiene_fiador'])) {
                        return $valor ? 'Sí' : 'No';
                    }

                    // Buscar nombres reales en lugar de IDs
                    if ($columna === 'office_id') {
                        $rel = Office::find($valor);

                        return $rel ? $rel->nombre : $valor;
                    }
                    if ($columna === 'asesor_id') {
                        $rel = User::find($valor);

                        return $rel ? $rel->name : $valor;
                    }
                    if ($columna === 'property_id') {
                        $rel = Property::find($valor);

                        return $rel ? trim(($rel->calle ?? '').' '.($rel->numero_exterior ?? '')) : $valor;
                    }

                    // Poner mayúsculas a los estatus (ej. 'analisis' -> 'Análisis')
                    if ($columna === 'estatus') {
                        return ucfirst($valor);
                    }

                    // Formatear arrays o repetidores para que no pongan código raro
                    if (is_array($valor) || (is_string($valor) && is_array(json_decode($valor, true)))) {
                        return 'Lista actualizada';
                    }

                    return $valor;
                };

                // 3. ARMAMOS EL MENSAJE
                foreach ($cambios as $columna => $nuevoValor) {
                    // Ignoramos campos técnicos o irrelevantes
                    if (in_array($columna, ['created_at', 'deleted_at', 'hash_id'])) {
                        continue;
                    }

                    $valorAnterior = $rent->getOriginal($columna);

                    $antiguo = $formatearValor($columna, $valorAnterior);
                    $nuevo = $formatearValor($columna, $nuevoValor);

                    // Solo reportar si realmente cambió visualmente (evita spam)
                    if ($antiguo !== $nuevo) {
                        $nombreEtiqueta = $nombresLegibles[$columna] ?? ucfirst(str_replace('_', ' ', $columna));
                        $mensaje .= "• $nombreEtiqueta: cambió de '$antiguo' a '$nuevo'.\n";
                        $cambiosReales++;
                    }
                }

                // --- DISPARADOR DE COBRO (CENTRO DE PAGOS) ---
                if ($rent->isDirty('estatus') && $rent->estatus === 'activa') {
                    PayableOperation::firstOrCreate(
                        [
                            'payable_type' => Rent::class,
                            'payable_id' => $rent->id,
                        ],
                        [
                            'user_id' => $rent->asesor_id, // El agente
                            'nombre_cliente' => $rent->tenant ? trim("{$rent->tenant->nombres} {$rent->tenant->primer_apellido}") : 'Sin nombre',
                            'fecha_firma' => $rent->fecha_firma ?? now(),
                            'monto_operacion' => $rent->renta ?? 0,
                            'monto_comision' => $rent->monto_comision ?? 0,
                            'regalia' => ($rent->monto_comision ?? 0) * 0.12, // 12% de regalía
                            'estatus' => 'pendiente de pago',
                            // Inicia el conteo regresivo de 10 días para la suspensión
                            'fecha_vencimiento' => now()->addDays(10),
                        ]
                    );
                }

                // 4. GUARDAR COMENTARIO (Solo si hubo cambios reales legibles)
                if ($cambiosReales > 0) {
                    RentComment::create([
                        'rent_id' => $rent->id,
                        'user_id' => auth()->id(),
                        'comment' => trim($mensaje),
                        'status' => 'activa',
                    ]);
                }
            }
        });
    }

    /**
     * Genera un folio único con el formato: RENT-2025-0001
     */
    public static function generateFolio(): string
    {
        $year = now()->year;
        $prefix = "RENT-{$year}-";

        // Obtener el último folio del año actual
        $lastRent = self::where('folio', 'like', "{$prefix}%")
            ->orderBy('folio', 'desc')
            ->first();

        if ($lastRent) {
            // Extraer el número del último folio
            $lastNumber = (int) substr($lastRent->folio, -4);
            $newNumber = $lastNumber + 1;
        } else {
            // Si no hay folios del año actual, empezar en 1
            $newNumber = 1;
        }

        // Formatear el número con 4 dígitos (0001, 0002, etc.)
        return $prefix.str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    // --- Relaciones ---

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    public function tenantRequests(): HasMany
    {
        return $this->hasMany(TenantRequest::class);
    }

    public function ownerRequests(): HasMany
    {
        return $this->hasMany(OwnerRequest::class);
    }

    public function tenantDocuments(): HasMany
    {
        return $this->hasMany(TenantDocument::class);
    }

    public function guarantorDocuments(): HasMany
    {
        return $this->hasMany(GuarantorDocument::class);
    }

    public function ownerDocuments(): HasMany
    {
        return $this->hasMany(OwnerDocument::class);
    }

    public function propertyDocuments(): HasMany
    {
        return $this->hasMany(PropertyDocument::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(RentComment::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    // Relaciones para modulo Administraciones
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Relación con el Asesor (Usuario del sistema)
     */
    public function asesor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asesor_id');
    }

    // Relación con PaymentSetting
    public function paymentSettings(): HasMany
    {
        return $this->hasMany(PaymentSetting::class);
    }
}
