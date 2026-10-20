<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DasborController;

Route::get('/', function () {
    return view('welcome');
});


// ADMIN
Route::prefix('admin')->name('admin.')->group(function () {
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
    
});