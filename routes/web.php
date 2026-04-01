<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\PublicTenantRequest;
use App\Livewire\PublicOwnerRequest;

Route::get('/', function () {
    return redirect('/admin/login');
});

// Ruta pública para que el inquilino llene su solicitud
Route::get('/solicitudes/inquilino/{record}', PublicTenantRequest::class)->name('solicitud.inquilino.publica');
// Ruta pública para que el propietario llene su solicitud
Route::get('/solicitudes/propietario/{record}', PublicOwnerRequest::class)->name('solicitud.propietario.publica');