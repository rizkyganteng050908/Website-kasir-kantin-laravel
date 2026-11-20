<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KasirController;

Route::get('/', [KasirController::class, 'index'])->name('kasir.index');
Route::post('/transaksi', [KasirController::class, 'store'])->name('kasir.store');
Route::post('/menu', [KasirController::class, 'storeMenu'])->name('menu.store');
Route::post('/menu/{id}/stok', [KasirController::class, 'updateStok'])->name('menu.stok');