<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DasborController;
use App\Http\Controllers\Admin\FasilitasController;
use App\Http\Controllers\Admin\KosController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// PENGGUNA
Route::prefix('pengguna')->name('pengguna.')->group(function () {
    Route::get('/beranda', function () {
        return view('pengguna.beranda');
    })->name('beranda');
    Route::get('/jelajah-kos', function () {
        return view('pengguna.jelajahKos');
    })->name('jelajahKos');
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
    });
});
