<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\PublicTenantRequest;

Route::get('/', function () {
    return redirect('/admin/login');
});

// Ruta pública para que el inquilino llene su solicitud
Route::get('/solicitudes/inquilino/{record}', PublicTenantRequest::class)->name('solicitud.inquilino.publica');