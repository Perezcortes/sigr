<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdvisorSearchController;
use App\Http\Controllers\Api\GeoController;
use App\Http\Controllers\Api\LeadWebhookController;
use App\Http\Controllers\Api\OwnerTenantProfileController;
use App\Http\Controllers\Api\PropertyController;
use App\Http\Controllers\Api\RentController;
use Illuminate\Support\Facades\Route;

// Rutas públicas
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/geo/estates', [GeoController::class, 'estates']);
Route::get('/geo/municipalities', [GeoController::class, 'municipalities']);
Route::post('/webhooks/leads/nocnok', [LeadWebhookController::class, 'handle']);
Route::get('/advisors/suggestions', [AdvisorSearchController::class, 'suggestions']);
Route::get('/advisors/search', [AdvisorSearchController::class, 'search']);
Route::get('/advisors/details', [AdvisorSearchController::class, 'details']);
Route::get('/advisors/featured', [AdvisorSearchController::class, 'featured']);
Route::get('/advisors/{slug}', [AdvisorSearchController::class, 'showBySlug']);

// Rutas protegidas (requieren autenticación)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    Route::get('/profile/owner', [OwnerTenantProfileController::class, 'showOwner']);
    Route::put('/profile/owner', [OwnerTenantProfileController::class, 'updateOwner']);
    Route::get('/profile/tenant', [OwnerTenantProfileController::class, 'showTenant']);
    Route::put('/profile/tenant', [OwnerTenantProfileController::class, 'updateTenant']);

    Route::get('/rents', [RentController::class, 'index']);
    Route::get('/rents/{id}', [RentController::class, 'show']);
    Route::post('/rents/{id}/finalizar', [RentController::class, 'finalizar']);
    Route::put('/rents/{id}/notifications', [RentController::class, 'updateNotifications']);

    Route::get('/properties', [PropertyController::class, 'index']);
    Route::post('/properties', [PropertyController::class, 'store']);
    Route::get('/properties/{property}', [PropertyController::class, 'show']);
    Route::patch('/properties/{property}', [PropertyController::class, 'update']);
    Route::post('/properties/{property}/images', [PropertyController::class, 'addImages']);
    Route::delete('/properties/{property}/images/{image}', [PropertyController::class, 'deleteImage']);
    Route::delete('/properties/{property}', [PropertyController::class, 'destroy']);
});

