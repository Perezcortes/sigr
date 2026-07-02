<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentSetting;
use App\Models\Rent;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PaymentReportController extends Controller
{
    // Lista los servicios/pagos activos de la renta, para el selector del modal "Reportar Pago"
    public function types(Request $request, int $rentId)
    {
        $rent = $this->viewableRent($request, $rentId);
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
                'es_variable'   => (bool) $s->es_variable,
                'monto'         => $s->monto !== null ? (float) $s->monto : null,
            ]);

        return response()->json(['data' => $tipos]);
    }

    // Obligaciones + pagos reportados desde el mes de inicio de la renta hasta el mes de fin de la renta
    // (o el mes actual si no tiene fecha de fin capturada), agrupados por mes.
    // Mismo cálculo de fecha límite que app/Livewire/PaymentManager.php (panel Filament),
    public function index(Request $request, int $rentId)
    {
        $rent = $this->viewableRent($request, $rentId);
        if (! $rent) {
            return response()->json(['message' => 'Renta no encontrada.'], 404);
        }

        $inicioRenta = $rent->start_date ?? $rent->fecha_firma ?? $rent->created_at;
        $mesInicio = Carbon::parse($inicioRenta)->startOfMonth();
        // Límite superior: mes de end_date si la renta lo tiene capturado (permite ver/adelantar meses
        // futuros hasta el fin del contrato); si no, el mes actual (sin adelanto)
        $mesFin = $rent->end_date ? Carbon::parse($rent->end_date)->startOfMonth() : now()->startOfMonth();
        if ($mesFin->lt($mesInicio)) {
            $mesFin = $mesInicio->copy();
        }
        $settings = PaymentSetting::where('rent_id', $rentId)->where('activo', true)->get();
        $today = now()->startOfDay();

        // Orden de los meses: el mes actual primero (si cae dentro del rango), seguido de los meses
        // futuros en orden ascendente (lo más accionable: pagar ahora o adelantar)
        $mesesOrdenados = [];
        $mesActual = now()->startOfMonth();
        if ($mesActual->betweenIncluded($mesInicio, $mesFin)) {
            for ($cursor = $mesActual->copy(); $cursor->lte($mesFin); $cursor->addMonth()) {
                $mesesOrdenados[] = $cursor->copy();
            }
            for ($cursor = $mesActual->copy()->subMonth(); $cursor->gte($mesInicio); $cursor->subMonth()) {
                $mesesOrdenados[] = $cursor->copy();
            }
        } else {
            for ($cursor = $mesFin->copy(); $cursor->gte($mesInicio); $cursor->subMonth()) {
                $mesesOrdenados[] = $cursor->copy();
            }
        }

        $periodos = [];

        foreach ($mesesOrdenados as $monthStart) {
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
                    'lote_pago'          => $service?->lote_pago,
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

    // Registra uno o varios pagos (uno por mes, en un solo reporte). No permite duplicar periodo/servicio.
    // La fecha de pago ya no la manda el cliente: siempre es "hoy" del servidor.
    public function store(Request $request, int $rentId)
    {
        $rent = $this->ownedRent($request, $rentId);
        if (! $rent) {
            return response()->json(['message' => 'Renta no encontrada.'], 404);
        }

        $data = $request->validate([
            'payment_setting_id'    => 'required|integer',
            'periodos_referencia'   => 'required|array|min:1',
            'periodos_referencia.*' => 'string|size:7',
            'forma_pago'            => 'required|string|in:efectivo,tarjeta,transferencia',
            'monto'                 => 'nullable|numeric|min:0',
            'observaciones'         => 'nullable|string|max:1000',
            'evidencia_base64'      => 'nullable|string',
            'evidencia_mime'        => 'nullable|string|in:image/jpeg,image/png,image/webp',
        ]);

        $setting = PaymentSetting::where('rent_id', $rentId)->find($data['payment_setting_id']);
        if (! $setting) {
            return response()->json(['message' => 'Servicio no encontrado.'], 404);
        }

        $periodos = array_values(array_unique($data['periodos_referencia']));

        if ($setting->es_variable && count($periodos) > 1) {
            return response()->json(['message' => 'Un servicio de monto variable no admite pago de varios meses en un solo reporte.'], 422);
        }
        if (count($periodos) === 1 && ! $request->filled('monto')) {
            return response()->json(['message' => 'El monto es requerido.'], 422);
        }
        if (count($periodos) > 1 && $setting->monto === null) {
            return response()->json(['message' => 'El servicio no tiene un monto fijo configurado.'], 422);
        }

        $existentes = Service::where('rent_id', $rentId)
            ->where('payment_setting_id', $setting->id)
            ->whereIn('periodo_referencia', $periodos)
            ->pluck('periodo_referencia');
        if ($existentes->isNotEmpty()) {
            return response()->json([
                'message' => 'Los siguientes periodos ya fueron reportados: ' . $existentes->implode(', '),
            ], 409);
        }

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

        $fechaPago = now();
        $lotePago = count($periodos) > 1 ? (string) Str::uuid() : null;

        $creados = DB::transaction(function () use ($periodos, $setting, $rentId, $data, $fechaPago, $evidenciaPath, $lotePago) {
            $servicios = [];
            foreach ($periodos as $periodo) {
                $monthStart = Carbon::createFromFormat('Y-m', $periodo)->startOfMonth();
                $dueDate = $this->resolveDueDateForMonth($setting, $monthStart) ?? $monthStart->copy();
                $monto = count($periodos) > 1 ? $setting->monto : ($data['monto'] ?? $setting->monto ?? 0);
                $estatus = $fechaPago->gt($dueDate) ? 'atrasado' : 'pagado';

                $servicios[] = Service::create([
                    'rent_id'             => $rentId,
                    'payment_setting_id'  => $setting->id,
                    'lote_pago'           => $lotePago,
                    'nombre'              => $setting->tipo . ' - ' . $periodo,
                    'tipo'                => $setting->tipo,
                    'frecuencia'          => $setting->frecuencia,
                    'mes_correspondiente' => ucfirst($monthStart->locale('es')->translatedFormat('F')),
                    'periodo_referencia'  => $periodo,
                    'fecha_pago'          => $fechaPago->toDateString(),
                    'fecha_vencimiento'   => $dueDate->toDateString(),
                    'monto'               => $monto,
                    'forma_pago'          => $data['forma_pago'],
                    'evidencia'           => $evidenciaPath,
                    'observaciones'       => $data['observaciones'] ?? null,
                    'estatus'             => $estatus,
                ]);
            }

            return $servicios;
        });

        return response()->json(['data' => array_map(fn (Service $service) => [
            'payment_setting_id' => $setting->id,
            'service_id'         => $service->id,
            'periodo_referencia' => $service->periodo_referencia,
            'tipo'               => $service->tipo,
            'icono'              => $setting->icono,
            'estado'             => $service->estatus,
            'fecha'              => $service->fecha_pago->format('d/m/y'),
            'monto'              => (float) $service->monto,
        ], $creados)], 201);
    }

    // Detalle completo de un pago ya reportado: fecha real, forma de pago, comprobante y,
    // si formó parte de un pago de varios meses (lote_pago), los meses que se pagaron junto con este.
    public function show(Request $request, int $rentId, int $serviceId)
    {
        $rent = $this->viewableRent($request, $rentId);
        if (! $rent) {
            return response()->json(['message' => 'Renta no encontrada.'], 404);
        }

        $service = Service::where('rent_id', $rentId)->with('paymentSetting')->find($serviceId);
        if (! $service) {
            return response()->json(['message' => 'Pago no encontrado.'], 404);
        }

        $mesesRelacionados = [];
        if ($service->lote_pago) {
            $mesesRelacionados = Service::where('rent_id', $rentId)
                ->where('lote_pago', $service->lote_pago)
                ->where('id', '!=', $service->id)
                ->orderBy('periodo_referencia')
                ->get()
                ->map(fn ($s) => [
                    'periodo_referencia'  => $s->periodo_referencia,
                    'mes_correspondiente' => $s->mes_correspondiente,
                ])
                ->values();
        }

        return response()->json(['data' => [
            'service_id'          => $service->id,
            'tipo'                => $service->tipo,
            'icono'               => $service->paymentSetting?->icono,
            'periodo_referencia'  => $service->periodo_referencia,
            'mes_correspondiente' => $service->mes_correspondiente,
            'fecha_pago'          => $service->fecha_pago->format('d/m/Y'),
            'forma_pago'          => $service->forma_pago,
            'monto'               => (float) $service->monto,
            'estado'              => $service->estatus,
            'observaciones'       => $service->observaciones,
            'evidencia_url'       => $service->evidencia ? Storage::disk('spaces')->url($service->evidencia) : null,
            'meses_relacionados'  => $mesesRelacionados,
        ]]);
    }

    private function ownedRent(Request $request, int $rentId): ?Rent
    {
        $owner = $request->user()->owner;
        if (! $owner) {
            return null;
        }

        return Rent::where('id', $rentId)->where('owner_id', $owner->id)->first();
    }

    // Igual que ownedRent(), pero también permite al inquilino (solo lectura: types/index/show)
    private function viewableRent(Request $request, int $rentId): ?Rent
    {
        $owner = $request->user()->owner;
        $tenant = $request->user()->tenant;
        if (! $owner && ! $tenant) {
            return null;
        }

        return Rent::where('id', $rentId)
            ->where(function ($q) use ($owner, $tenant) {
                if ($owner) {
                    $q->orWhere('owner_id', $owner->id);
                }
                if ($tenant) {
                    $q->orWhere('tenant_id', $tenant->id);
                }
            })
            ->first();
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
