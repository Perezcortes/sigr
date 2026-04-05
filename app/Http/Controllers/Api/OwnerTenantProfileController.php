<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Owner;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Perfil propietario (Owner) e inquilino (Tenant) para la app consumidora (Bearer Sanctum).
 */
class OwnerTenantProfileController extends Controller
{
    public function showOwner(Request $request): JsonResponse
    {
        $owner = Owner::where('user_id', $request->user()->id)->first();

        return response()->json([
            'data' => $owner ? $owner->toArray() : [],
        ]);
    }

    public function updateOwner(Request $request): JsonResponse
    {
        $user = $request->user();
        $fillable = array_values(array_diff((new Owner)->getFillable(), ['user_id']));
        $payload = $request->only($fillable);

        $owner = Owner::updateOrCreate(
            ['user_id' => $user->id],
            array_merge($payload, ['user_id' => $user->id])
        );

        return response()->json([
            'data' => $owner->fresh()->toArray(),
            'message' => 'Perfil de propietario guardado.',
        ]);
    }

    public function showTenant(Request $request): JsonResponse
    {
        $tenant = Tenant::where('user_id', $request->user()->id)->first();

        return response()->json([
            'data' => $tenant ? $tenant->toArray() : [],
        ]);
    }

    public function updateTenant(Request $request): JsonResponse
    {
        $user = $request->user();
        $fillable = array_values(array_diff((new Tenant)->getFillable(), ['user_id']));
        $payload = $request->only($fillable);

        $tenant = Tenant::updateOrCreate(
            ['user_id' => $user->id],
            array_merge($payload, ['user_id' => $user->id])
        );

        return response()->json([
            'data' => $tenant->fresh()->toArray(),
            'message' => 'Perfil de inquilino guardado.',
        ]);
    }
}
