<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DasborController;

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
    // LOGIN (UBAH jika udah ada controller)
    Route::get('/login', function () {
        return view('admin.login'); 
    })->name('login');

    // Dummy POST untuk simulasi tombol "Masuk" ditekan
    Route::post('/login', function () {
        // Pura-puranya login berhasil, langsung lempar ke Dasbor
        return redirect()->route('admin.dasbor'); 
    })->name('login.submit');

    // Dummy POST untuk simulasi tombol "Keluar" di sidebar
    Route::post('/logout', function () {
        return redirect()->route('admin.login');
    })->name('logout');

    Route::get('/', fn () => redirect()->route('admin.dasbor'));
    Route::get('/dasbor', [DasborController::class, 'index'])->name('dasbor');
    // DATA KOS
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