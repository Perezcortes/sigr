<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Estate;
use App\Models\Municipality;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// Endpoints públicos (sin auth) para poblar los selects de estado y municipio en la app
class GeoController extends Controller
{
    // Devuelve los 32 estados ordenados alfabéticamente
    public function estates(): JsonResponse
    {
        $estates = Estate::orderBy('nombre')->get(['id', 'nombre']);

        return response()->json($estates);
    }

    // state_id en municipios.json usa orden alfabético de estados, no el orden INEGI
    public function municipalities(Request $request): JsonResponse
    {
        $request->validate([
            'estate_id' => ['required', 'integer', 'exists:estates,id'],
        ]);

        $municipalities = Municipality::where('state_id', $request->estate_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($municipalities);
    }
}
