<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Estate;
use App\Models\Municipality;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GeoController extends Controller
{
    public function estates(): JsonResponse
    {
        $estates = Estate::orderBy('nombre')->get(['id', 'nombre']);

        return response()->json($estates);
    }

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
