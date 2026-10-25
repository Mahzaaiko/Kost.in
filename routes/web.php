<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DasborController;
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

        // DATA KOS (masih view statis, diganti controller di Fase Admin)
        Route::prefix('datakos')->group(function () {
            Route::get('/', function () {
                return view('admin.datakos');
            })->name('datakos');
            Route::get('/tambah', function () {
                return view('admin.datakosTambah');
            })->name('datakos.tambah');
            Route::get('/{id}', function ($id) {
                return view('admin.datakosDetail', ['id' => $id]);
            })->name('datakos.detail');
            Route::get('/{id}/edit', function ($id) {
                return view('admin.datakosEdit', ['id' => $id]);
            })->name('datakos.edit');
        });

        Route::get('/fasilitas', function () {
            return view('admin.fasilitas');
        })->name('fasilitas');
    });
});
