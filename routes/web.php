<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DasborController;
use App\Http\Controllers\Admin\FasilitasController;
use App\Http\Controllers\Admin\JaringanJalanController;
use App\Http\Controllers\Admin\KosController;
use App\Http\Controllers\Admin\PlusCodeController;
use App\Http\Controllers\BerandaController;
use App\Http\Controllers\JelajahController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// PENGGUNA
Route::prefix('pengguna')->name('pengguna.')->group(function () {
    Route::get('/beranda', BerandaController::class)->name('beranda');
    Route::get('/jelajah-kos', JelajahController::class)->name('jelajahKos');
});

// ADMIN
Route::prefix('admin')->name('admin.')->group(function () {
    // Login hanya untuk tamu; admin yang sudah login diarahkan ke dasbor.
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'create'])->name('login');
        Route::post('/login', [AuthController::class, 'store'])->name('login.submit');
    });

    // Semua halaman admin di bawah ini wajib login.
    Route::middleware('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

        Route::get('/', fn () => redirect()->route('admin.dasbor'));
        Route::get('/dasbor', [DasborController::class, 'index'])->name('dasbor');

        // Ubah Plus Code Google Maps ke koordinat (dipakai form Tambah/Edit kos).
        Route::get('/plus-code', PlusCodeController::class)->name('plusCode');

        // DATA KOS
        Route::prefix('datakos')->name('datakos')->controller(KosController::class)->group(function () {
            Route::get('/', 'index');
            Route::get('/tambah', 'create')->name('.tambah');
            Route::post('/', 'store')->name('.store');
            Route::get('/{kos}', 'show')->whereNumber('kos')->name('.detail');
            Route::get('/{kos}/edit', 'edit')->whereNumber('kos')->name('.edit');
            Route::put('/{kos}', 'update')->whereNumber('kos')->name('.update');
            Route::delete('/{kos}', 'destroy')->whereNumber('kos')->name('.hapus');
            Route::patch('/{kos}/status', 'status')->whereNumber('kos')->name('.status');
        });

        // FASILITAS
        Route::prefix('fasilitas')->name('fasilitas')->controller(FasilitasController::class)->group(function () {
            Route::get('/', 'index');
            Route::post('/', 'store')->name('.store');
            Route::put('/{fasilitas}', 'update')->whereNumber('fasilitas')->name('.update');
            Route::delete('/{fasilitas}', 'destroy')->whereNumber('fasilitas')->name('.hapus');
        });

        // JARINGAN JALAN (gang hasil survei dan validasi jarak)
        Route::prefix('jaringan-jalan')->name('jaringanJalan')->controller(JaringanJalanController::class)->group(function () {
            Route::get('/', 'index');
            Route::post('/', 'store')->name('.store');
            Route::delete('/{rekaman}', 'destroy')->whereNumber('rekaman')->name('.hapus');
            Route::get('/bandingkan/{kos}', 'bandingkan')->whereNumber('kos')->name('.bandingkan');
            Route::get('/ekspor', 'ekspor')->name('.ekspor');
        });
    });
});