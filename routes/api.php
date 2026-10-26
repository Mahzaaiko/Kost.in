<?php

use App\Http\Controllers\Api\KosController;
use Illuminate\Support\Facades\Route;

// API publik untuk halaman pengguna (tanpa login). Prefix /api otomatis.
Route::middleware('throttle:60,1')->name('api.')->group(function () {
    Route::get('/kos', [KosController::class, 'index'])->name('kos.index');
    Route::post('/kos/{kos}/dilihat', [KosController::class, 'dilihat'])->whereNumber('kos')->name('kos.dilihat');
});