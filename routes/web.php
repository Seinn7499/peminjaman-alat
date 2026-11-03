<?php

use App\Http\Controllers\PeminjamanController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/peminjaman'));

Route::get('/peminjaman', [PeminjamanController::class, 'index']);
Route::post('/peminjaman', [PeminjamanController::class, 'store']);
Route::get('/peminjaman/{id}/edit', [PeminjamanController::class, 'edit'])->whereNumber('id');
Route::put('/peminjaman/{id}', [PeminjamanController::class, 'update'])->whereNumber('id');
Route::delete('/peminjaman/{id}', [PeminjamanController::class, 'destroy'])->whereNumber('id');