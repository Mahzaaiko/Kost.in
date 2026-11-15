<?php

use App\Http\Controllers\Api\KosController;
use App\Http\Controllers\Api\PetaController;
use Illuminate\Support\Facades\Route;

// API publik untuk halaman pengguna (tanpa login). Prefix /api otomatis.
Route::middleware('throttle:60,1')->name('api.')->group(function () {
    Route::get('/kos', [KosController::class, 'index'])->name('kos.index');
    Route::post('/kos/{kos}/dilihat', [KosController::class, 'dilihat'])->whereNumber('kos')->name('kos.dilihat');
    Route::get('/kos/{kos}/rute', [KosController::class, 'rute'])->whereNumber('kos')->name('kos.rute');

    // Layer tematik peta (GeoJSON).
    Route::get('/peta/jangkauan', [PetaController::class, 'jangkauan'])->name('peta.jangkauan');
    Route::get('/peta/jaringan-jalan', [PetaController::class, 'jaringanJalan'])->name('peta.jaringanJalan');
});