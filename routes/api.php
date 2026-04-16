<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdvisorSearchController;
use App\Http\Controllers\Api\LeadWebhookController;
use App\Http\Controllers\Api\OwnerTenantProfileController;
use App\Http\Controllers\Api\PropertyController;
use Illuminate\Support\Facades\Route;

// Rutas públicas
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/webhooks/leads/nocnok', [LeadWebhookController::class, 'handle']);
Route::get('/advisors/suggestions', [AdvisorSearchController::class, 'suggestions']);
Route::get('/advisors/search', [AdvisorSearchController::class, 'search']);
Route::get('/advisors/details', [AdvisorSearchController::class, 'details']);

// Rutas protegidas (requieren autenticación)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    Route::get('/profile/owner', [OwnerTenantProfileController::class, 'showOwner']);
    Route::put('/profile/owner', [OwnerTenantProfileController::class, 'updateOwner']);
    Route::get('/profile/tenant', [OwnerTenantProfileController::class, 'showTenant']);
    Route::put('/profile/tenant', [OwnerTenantProfileController::class, 'updateTenant']);

    Route::get('/properties', [PropertyController::class, 'index']);
    Route::post('/properties', [PropertyController::class, 'store']);
    Route::get('/properties/{property}', [PropertyController::class, 'show']);
});

