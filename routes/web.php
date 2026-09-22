<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\AssetBatchController;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])
        ->name('login');

    Route::post('/login', [LoginController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::view('/dashboard', 'dashboard')
        ->name('dashboard');
        Route::get('/categories/create', [CategoryController::class, 'create'])
    ->name('categories.create');

Route::post('/categories', [CategoryController::class, 'store'])
    ->name('categories.store');

    Route::post('/logout', [LoginController::class, 'destroy'])
        ->name('logout');

        Route::get('/categories', [CategoryController::class, 'index'])
    ->name('categories.index');

    Route::get('/items/create', [ItemController::class, 'create'])
    ->name('items.create');

Route::post('/items', [ItemController::class, 'store'])
    ->name('items.store');

    Route::get('/assets/batch/create', [AssetBatchController::class, 'create'])
    ->name('assets.batch.create');

Route::post('/assets/batch', [AssetBatchController::class, 'store'])
    ->name('assets.batch.store');
});