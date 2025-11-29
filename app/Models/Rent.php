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
        'office_id',
        'tenant_id',
        'owner_id',
        'start_date',
        'end_date',
        'amount',
        'payment_frequency',
        // Datos de la renta
        'folio',
        'sucursal',
        'abogado',
        'inmobiliaria',
        'estatus',
        'tipo_inmueble',
        'tipo_poliza',
        'renta',
        'poliza',
        // Fiador
        'tiene_fiador',
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
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'amount' => 'decimal:2',
        'renta' => 'decimal:2',
        'poliza' => 'decimal:2',
    ];

    /**
     * Boot del modelo para generar el folio automáticamente
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($rent) {
            if (empty($rent->folio)) {
                $rent->folio = self::generateFolio();
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
        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
    
    // --- Relaciones ---

    public function office(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Office::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Owner::class);
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
}