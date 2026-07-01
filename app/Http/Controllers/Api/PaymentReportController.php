<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentSetting;
use App\Models\Rent;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class PaymentReportController extends Controller
{
    // Lista los servicios/pagos activos de la renta, para el selector del modal "Reportar Pago"
    public function types(Request $request, int $rentId)
    {
        $rent = $this->ownedRent($request, $rentId);
        if (! $rent) {
            return response()->json(['message' => 'Renta no encontrada.'], 404);
        }

        $tipos = PaymentSetting::where('rent_id', $rentId)
            ->where('activo', true)
            ->orderByDesc('es_base_renta')
            ->orderBy('tipo')
            ->get()
            ->map(fn ($s) => [
                'id'            => $s->id,
                'tipo'          => $s->tipo,
                'icono'         => $s->icono,
                'es_base_renta' => (bool) $s->es_base_renta,
            ]);

        return response()->json(['data' => $tipos]);
    }

    // Obligaciones + pagos reportados desde el mes de inicio de la renta hasta el mes actual, agrupados por mes.
    // Mismo cálculo de fecha límite que app/Livewire/PaymentManager.php (panel Filament),
    public function index(Request $request, int $rentId)
    {
        $rent = $this->ownedRent($request, $rentId);
        if (! $rent) {
            return response()->json(['message' => 'Renta no encontrada.'], 404);
        }

        $inicioRenta = $rent->start_date ?? $rent->fecha_firma ?? $rent->created_at;
        $mesInicio = Carbon::parse($inicioRenta)->startOfMonth();
        $meses = max(1, $mesInicio->diffInMonths(now()->startOfMonth()) + 1);
        $settings = PaymentSetting::where('rent_id', $rentId)->where('activo', true)->get();
        $today = now()->startOfDay();

        $periodos = [];

        for ($i = 0; $i < $meses; $i++) {
            $monthStart = now()->startOfMonth()->subMonths($i);
            $periodKey = $monthStart->format('Y-m');

            $services = Service::where('rent_id', $rentId)
                ->where('periodo_referencia', $periodKey)
                ->whereIn('payment_setting_id', $settings->pluck('id'))
                ->get()
                ->keyBy('payment_setting_id');

            $pagos = [];
            foreach ($settings as $setting) {
                $dueDate = $this->resolveDueDateForMonth($setting, $monthStart);
                if (! $dueDate) {
                    continue;
                }

                $service = $services->get($setting->id);
                $pagos[] = [
                    'payment_setting_id' => $setting->id,
                    'service_id'         => $service?->id,
                    'tipo'               => $setting->tipo,
                    'icono'              => $setting->icono,
                    'estado'             => $service ? $service->estatus : ($dueDate->lt($today) ? 'vencido' : 'por_vencer'),
                    'fecha'              => $service?->fecha_pago?->format('d/m/y') ?? $dueDate->format('d/m/y'),
                    'monto'              => $service ? (float) $service->monto : (float) ($setting->monto ?? 0),
                ];
            }

            $periodos[] = [
                'periodo'   => $periodKey,
                'mes_label' => ucfirst($monthStart->locale('es')->translatedFormat('F')),
                'pagos'     => $pagos,
            ];
        }

        return response()->json(['data' => $periodos]);
    }

    // Registra un pago (crea un Service). No permite duplicar el mismo periodo/servicio.
    public function store(Request $request, int $rentId)
    {
        $rent = $this->ownedRent($request, $rentId);
        if (! $rent) {
            return response()->json(['message' => 'Renta no encontrada.'], 404);
        }

        $data = $request->validate([
            'payment_setting_id' => 'required|integer',
            'periodo_referencia' => 'required|string|size:7',
            'fecha_pago'         => 'required|date',
            'forma_pago'         => 'required|string|in:efectivo,tarjeta,transferencia',
            'monto'              => 'required|numeric|min:0',
            'observaciones'      => 'nullable|string|max:1000',
            'evidencia_base64'   => 'nullable|string',
            'evidencia_mime'     => 'nullable|string|in:image/jpeg,image/png,image/webp',
        ]);

        $setting = PaymentSetting::where('rent_id', $rentId)->find($data['payment_setting_id']);
        if (! $setting) {
            return response()->json(['message' => 'Servicio no encontrado.'], 404);
        }

        $exists = Service::where('rent_id', $rentId)
            ->where('payment_setting_id', $setting->id)
            ->where('periodo_referencia', $data['periodo_referencia'])
            ->exists();
        if ($exists) {
            return response()->json(['message' => 'Ese periodo ya fue reportado.'], 409);
        }

        $monthStart = Carbon::createFromFormat('Y-m', $data['periodo_referencia'])->startOfMonth();
        $dueDate = $this->resolveDueDateForMonth($setting, $monthStart) ?? $monthStart->copy();
        $paidDate = Carbon::parse($data['fecha_pago']);
        $estatus = $paidDate->gt($dueDate) ? 'atrasado' : 'pagado';

        $evidenciaPath = null;
        if (! empty($data['evidencia_base64'])) {
            $content = base64_decode(preg_replace('/^data:[^;]+;base64,/', '', $data['evidencia_base64']), true);
            if ($content === false || ! $this->validateImageContent($content, $data['evidencia_mime'] ?? '')) {
                return response()->json(['message' => 'Comprobante inválido.'], 422);
            }
            $ext = match ($data['evidencia_mime']) {
                'image/png'  => 'png',
                'image/webp' => 'webp',
                default      => 'jpg',
            };
            $evidenciaPath = "rents/{$rentId}/comprobantes/" . uniqid() . ".{$ext}";
            Storage::disk('spaces')->put($evidenciaPath, $content, 'public');
        }

        $service = Service::create([
            'rent_id'             => $rentId,
            'payment_setting_id'  => $setting->id,
            'nombre'              => $setting->tipo . ' - ' . $data['periodo_referencia'],
            'tipo'                => $setting->tipo,
            'frecuencia'          => $setting->frecuencia,
            'mes_correspondiente' => ucfirst($monthStart->locale('es')->translatedFormat('F')),
            'periodo_referencia'  => $data['periodo_referencia'],
            'fecha_pago'          => $data['fecha_pago'],
            'fecha_vencimiento'   => $dueDate->toDateString(),
            'monto'               => $data['monto'],
            'forma_pago'          => $data['forma_pago'],
            'evidencia'           => $evidenciaPath,
            'observaciones'       => $data['observaciones'] ?? null,
            'estatus'             => $estatus,
        ]);

        return response()->json(['data' => [
            'payment_setting_id' => $setting->id,
            'service_id'         => $service->id,
            'tipo'               => $service->tipo,
            'icono'              => $setting->icono,
            'estado'             => $service->estatus,
            'fecha'              => $service->fecha_pago->format('d/m/y'),
            'monto'              => (float) $service->monto,
        ]], 201);
    }

    private function ownedRent(Request $request, int $rentId): ?Rent
    {
        $owner = $request->user()->owner;
        if (! $owner) {
            return null;
        }

        return Rent::where('id', $rentId)->where('owner_id', $owner->id)->first();
    }

    private function resolveDueDateForMonth(PaymentSetting $setting, Carbon $candidateDate): ?Carbon
    {
        $interval = max(1, (int) ($setting->meses_intervalo ?: PaymentSetting::intervalForFrequency($setting->frecuencia ?? 'Mensual')));

        if (($setting->frecuencia ?? 'Mensual') === 'Mensual') {
            $day = min(max((int) ($setting->dia_pago ?? 1), 1), $candidateDate->daysInMonth);

            return Carbon::create($candidateDate->year, $candidateDate->month, $day)->startOfDay();
        }

        if (! $setting->fecha_limite_pago) {
            return null;
        }

        $anchor = $setting->fecha_limite_pago->copy()->startOfDay();
        $diffMonths = (($candidateDate->year - $anchor->year) * 12) + ($candidateDate->month - $anchor->month);

        if ($diffMonths < 0 || $diffMonths % $interval !== 0) {
            return null;
        }

        $day = min($anchor->day, $candidateDate->daysInMonth);

        return Carbon::create($candidateDate->year, $candidateDate->month, $day)->startOfDay();
    }

    // Mismas reglas de magic bytes que Api\PropertyController::validateDocumentContent(), acotado a imágenes
    private function validateImageContent(string $content, string $mime): bool
    {
        if (strlen($content) < 8) {
            return false;
        }
        $header = substr($content, 0, 8);

        return match ($mime) {
            'image/jpeg' => str_starts_with($header, "\xFF\xD8\xFF"),
            'image/png'  => str_starts_with($header, "\x89PNG\r\n\x1a\n"),
            'image/webp' => str_starts_with($header, 'RIFF') && substr($content, 8, 4) === 'WEBP',
            default      => false,
        };
    }
}
