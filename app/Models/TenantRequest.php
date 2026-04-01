<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantRequest extends Model
{
    use HasFactory;
    
    // Con esta única línea permitimos que todos los campos se guarden
    protected $guarded = [];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'fecha_ingreso' => 'date',
        'fecha_constitucion' => 'date',
        'fecha_escritura_facultades' => 'date',
        'fecha_inscripcion_facultades' => 'date',
        
        'precio_renta' => 'decimal:2',
        'deposito_garantia' => 'decimal:2',
        'costo_mantenimiento_mensual' => 'decimal:2',
        'monto_cobertura_seguro' => 'decimal:2',
    ];

    /**
     * Relación con Tenant
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Relación con Rent
     */
    public function rent(): BelongsTo
    {
        return $this->belongsTo(Rent::class);
    }

    /**
     * Obtiene el nombre completo del inquilino
     */
    public function getNombreCompletoAttribute(): string
    {
        return trim($this->nombres . ' ' . $this->primer_apellido . ' ' . ($this->segundo_apellido ?? ''));
    }

    public function syncWithTenant()
    {
        if ($this->tenant) {
            $this->tenant->update([
                'nombres' => $this->nombres,
                'primer_apellido' => $this->primer_apellido,
                'segundo_apellido' => $this->segundo_apellido,
                'email' => $this->email,
                'telefono_celular' => $this->telefono_celular,
                'telefono_fijo' => $this->telefono_fijo,
                'estado_civil' => $this->estado_civil,
                'sexo' => $this->sexo,
                'nacionalidad' => $this->nacionalidad,
                'nacionalidad_especifica' => $this->nacionalidad_especifica,
                'tipo_identificacion' => $this->tipo_identificacion,
                'rfc' => $this->rfc,
                'curp' => $this->curp,
            ]);
        }
    }
}