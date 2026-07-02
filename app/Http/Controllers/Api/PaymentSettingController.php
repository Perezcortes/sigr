<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentReminder;
use App\Models\PaymentSetting;
use App\Models\Rent;
use Illuminate\Http\Request;

class PaymentSettingController extends Controller
{
    // Devuelve todos los payment_settings de una renta con sus recordatorios (propietario o inquilino, solo lectura)
    public function index(Request $request, int $rentId)
    {
        $owner = $request->user()->owner;
        $tenant = $request->user()->tenant;
        if (! $owner && ! $tenant) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $rent = Rent::where('id', $rentId)
            ->where(function ($q) use ($owner, $tenant) {
                if ($owner) {
                    $q->orWhere('owner_id', $owner->id);
                }
                if ($tenant) {
                    $q->orWhere('tenant_id', $tenant->id);
                }
            })
            ->first();
        if (! $rent) {
            return response()->json(['message' => 'Renta no encontrada.'], 404);
        }

        $settings = PaymentSetting::with('reminders')
            ->where('rent_id', $rentId)
            ->orderByDesc('es_base_renta')
            ->orderBy('id')
            ->get()
            ->map(fn ($s) => $this->toArray($s));

        return response()->json(['data' => $settings]);
    }

    // Crea un nuevo tipo de pago para la renta
    public function store(Request $request, int $rentId)
    {
        $owner = $request->user()->owner;
        if (! $owner) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $rent = Rent::where('id', $rentId)->where('owner_id', $owner->id)->first();
        if (! $rent) {
            return response()->json(['message' => 'Renta no encontrada.'], 404);
        }

        $data = $request->validate([
            'tipo'        => 'required|string|max:64',
            'frecuencia'  => 'required|string|in:Mensual,Bimestral,Trimestral,Semestral,Anual',
            'monto'       => 'nullable|numeric|min:0',
            'moneda'      => 'nullable|string|max:8',
            'es_variable' => 'boolean',
            'dia_pago'    => 'nullable|integer|min:1|max:31',
            'dias_antes'  => 'nullable|integer|min:0',
            'direccion'   => 'nullable|string|in:antes,despues',
            'icono'       => 'nullable|string|max:10',
            'fecha_limite_pago' => 'nullable|date',
        ]);

        // Unicidad de ícono dentro de la misma renta
        if (!empty($data['icono'])) {
            $duplicate = PaymentSetting::where('rent_id', $rentId)->where('icono', $data['icono'])->exists();
            if ($duplicate) {
                return response()->json(['message' => 'Este ícono ya está en uso por otro servicio.'], 422);
            }
        }

        $esMensual = $data['frecuencia'] === 'Mensual';

        $setting = PaymentSetting::create([
            'rent_id' => $rentId,
            'tipo' => $data['tipo'],
            'frecuencia' => $data['frecuencia'],
            'dia_pago' => $esMensual ? ($data['dia_pago'] ?? 5) : null,
            'meses_intervalo' => PaymentSetting::intervalForFrequency($data['frecuencia']),
            'fecha_limite_pago' => $esMensual ? null : ($data['fecha_limite_pago'] ?? now()->toDateString()),
            'monto' => ($data['es_variable'] ?? false) ? null : ($data['monto'] ?? 0),
            'moneda' => $data['moneda'] ?? 'MXN',
            'es_variable' => $data['es_variable'] ?? false,
            'activo' => true,
            'es_base_renta' => false,
            'icono' => $data['icono'] ?? null,
        ]);

        // Recordatorio inicial con los días y dirección indicados (o 3 días / antes por defecto)
        $setting->reminders()->create([
            'dias_antes' => $data['dias_antes'] ?? 3,
            'direccion'  => $data['direccion'] ?? 'antes',
            'activo'     => true,
        ]);

        $setting->load('reminders');

        return response()->json(['data' => $this->toArray($setting)], 201);
    }

    // Actualiza un campo específico de un payment_setting
    public function update(Request $request, int $id)
    {
        $owner = $request->user()->owner;
        if (! $owner) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $setting = PaymentSetting::whereHas('rent', fn ($q) => $q->where('owner_id', $owner->id))
            ->find($id);

        if (! $setting) {
            return response()->json(['message' => 'Configuración no encontrada.'], 404);
        }

        $data = $request->validate([
            'activo'      => 'sometimes|boolean',
            'frecuencia'  => 'sometimes|string|in:Mensual,Bimestral,Trimestral,Semestral,Anual',
            'monto'       => 'sometimes|nullable|numeric|min:0',
            'es_variable' => 'sometimes|boolean',
            'dia_pago'    => 'sometimes|nullable|integer|min:1|max:31',
            'icono'       => 'sometimes|nullable|string|max:10',
        ]);

        // Unicidad de ícono dentro de la misma renta al cambiar
        if (!empty($data['icono'])) {
            $duplicate = PaymentSetting::where('rent_id', $setting->rent_id)
                ->where('icono', $data['icono'])
                ->where('id', '!=', $id)
                ->exists();
            if ($duplicate) {
                return response()->json(['message' => 'Este ícono ya está en uso por otro servicio.'], 422);
            }
        }

        if (isset($data['activo']) && ! $data['activo'] && $setting->es_base_renta) {
            return response()->json(['message' => 'La renta base no se puede desactivar.'], 422);
        }

        if (isset($data['frecuencia'])) {
            $setting->meses_intervalo = PaymentSetting::intervalForFrequency($data['frecuencia']);

            if ($data['frecuencia'] === 'Mensual') {
                $setting->fecha_limite_pago = null;
                $setting->dia_pago = $setting->dia_pago ?: 5;
            } else {
                $setting->dia_pago = null;
                $setting->fecha_limite_pago = $setting->fecha_limite_pago ?? now()->toDateString();
            }
        }

        if (isset($data['es_variable']) && $data['es_variable']) {
            $setting->monto = null;
        }

        $setting->fill($data)->save();

        $setting->load('reminders');

        return response()->json(['data' => $this->toArray($setting)]);
    }

    // Elimina un tipo de pago (no se puede eliminar la renta base)
    public function destroy(Request $request, int $id)
    {
        $owner = $request->user()->owner;
        if (! $owner) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $setting = PaymentSetting::whereHas('rent', fn ($q) => $q->where('owner_id', $owner->id))
            ->find($id);

        if (! $setting) {
            return response()->json(['message' => 'Configuración no encontrada.'], 404);
        }

        if ($setting->es_base_renta) {
            return response()->json(['message' => 'La renta base no se puede eliminar.'], 422);
        }

        $setting->delete();

        return response()->json(['message' => 'Eliminado.']);
    }

    // Agrega un recordatorio a un payment_setting
    public function addReminder(Request $request, int $id)
    {
        $owner = $request->user()->owner;
        if (! $owner) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $setting = PaymentSetting::whereHas('rent', fn ($q) => $q->where('owner_id', $owner->id))
            ->find($id);

        if (! $setting) {
            return response()->json(['message' => 'Configuración no encontrada.'], 404);
        }

        $data = $request->validate([
            'dias_antes' => 'nullable|integer|min:0',
            'direccion'  => 'nullable|string|in:antes,despues',
        ]);

        $nextDays = (int) ($setting->reminders()->max('dias_antes') ?? 0) + 1;

        $reminder = $setting->reminders()->create([
            'dias_antes' => $data['dias_antes'] ?? $nextDays,
            'direccion'  => $data['direccion'] ?? 'antes',
            'activo'     => true,
        ]);

        return response()->json(['data' => $this->reminderToArray($reminder)], 201);
    }

    // Actualiza los días de un recordatorio
    public function updateReminder(Request $request, int $settingId, int $reminderId)
    {
        $owner = $request->user()->owner;
        if (! $owner) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $reminder = PaymentReminder::whereHas(
            'paymentSetting.rent',
            fn ($q) => $q->where('owner_id', $owner->id)
        )->where('payment_setting_id', $settingId)->find($reminderId);

        if (! $reminder) {
            return response()->json(['message' => 'Recordatorio no encontrado.'], 404);
        }

        $data = $request->validate([
            'dias_antes' => 'required|integer|min:0',
            'direccion'  => 'nullable|string|in:antes,despues',
        ]);

        $reminder->dias_antes = max(0, $data['dias_antes']);
        if (isset($data['direccion'])) {
            $reminder->direccion = $data['direccion'];
        }
        $reminder->save();

        return response()->json(['data' => $this->reminderToArray($reminder)]);
    }

    // Elimina un recordatorio
    public function removeReminder(Request $request, int $settingId, int $reminderId)
    {
        $owner = $request->user()->owner;
        if (! $owner) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $reminder = PaymentReminder::whereHas(
            'paymentSetting.rent',
            fn ($q) => $q->where('owner_id', $owner->id)
        )->where('payment_setting_id', $settingId)->find($reminderId);

        if (! $reminder) {
            return response()->json(['message' => 'Recordatorio no encontrado.'], 404);
        }

        $reminder->delete();

        return response()->json(['message' => 'Recordatorio eliminado.']);
    }

    private function toArray(PaymentSetting $s): array
    {
        return [
            'id'            => $s->id,
            'tipo'          => $s->tipo,
            'frecuencia'    => $s->frecuencia,
            'dia_pago'      => $s->dia_pago,
            'monto'         => $s->monto !== null ? (float) $s->monto : null,
            'moneda'        => $s->moneda ?? 'MXN',
            'es_variable'   => (bool) $s->es_variable,
            'activo'        => (bool) $s->activo,
            'es_base_renta' => (bool) $s->es_base_renta,
            'icono'         => $s->icono,
            'recordatorios' => $s->reminders->map(fn ($r) => $this->reminderToArray($r))->values()->all(),
        ];
    }

    private function reminderToArray(PaymentReminder $r): array
    {
        return [
            'id'         => $r->id,
            'dias_antes' => $r->dias_antes,
            'direccion'  => $r->direccion ?? 'antes',
        ];
    }
}
