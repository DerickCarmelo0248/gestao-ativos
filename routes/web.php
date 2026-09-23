<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\AssetBatchController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\StockEntryController;
use App\Http\Controllers\StockBalanceController;
use App\Http\Controllers\StockExitController;
use App\Http\Controllers\StockReplacementController;
use App\Http\Controllers\AssetExitController;
use App\Http\Controllers\AssetReplacementController;
use App\Http\Controllers\AssetReturnController;
use App\Http\Controllers\DisposalContainerController;
use App\Http\Controllers\DashboardController;

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
    Route::get('/dashboard', [DashboardController::class, 'index'])
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

    Route::get('/assets', [AssetController::class, 'index'])
    ->name('assets.index');

    Route::get('/assets/{asset}', [AssetController::class, 'show'])
    ->whereNumber('asset')
    ->name('assets.show');

    Route::get('/stock-entries/create', [StockEntryController::class, 'create'])
    ->name('stock-entries.create');

Route::post('/stock-entries', [StockEntryController::class, 'store'])
    ->name('stock-entries.store');

    Route::get('/stock-balances', [StockBalanceController::class, 'index'])
    ->name('stock-balances.index');

    Route::get(
    '/stock-balances/{stockBalance}',
    [StockBalanceController::class, 'show']
)
    ->whereNumber('stockBalance')
    ->name('stock-balances.show');

    Route::get('/stock-exits/create', [StockExitController::class, 'create'])
    ->name('stock-exits.create');

Route::post('/stock-exits', [StockExitController::class, 'store'])
    ->name('stock-exits.store');

    Route::get(
    '/stock-replacements',
    [StockReplacementController::class, 'index']
)->name('stock-replacements.index');

Route::post(
    '/stock-replacements/{replacement}/complete',
    [StockReplacementController::class, 'complete']
)
    ->whereNumber('replacement')
    ->name('stock-replacements.complete');

    Route::get('/asset-exits/create', [AssetExitController::class, 'create'])
    ->name('asset-exits.create');

Route::post('/asset-exits', [AssetExitController::class, 'store'])
    ->name('asset-exits.store');

    Route::get(
    '/asset-replacements',
    [AssetReplacementController::class, 'index']
)->name('asset-replacements.index');

Route::get(
    '/asset-replacements/{replacement}/receive',
    [AssetReplacementController::class, 'edit']
)
    ->whereNumber('replacement')
    ->name('asset-replacements.edit');

Route::post(
    '/asset-replacements/{replacement}/complete',
    [AssetReplacementController::class, 'complete']
)
    ->whereNumber('replacement')
    ->name('asset-replacements.complete');

    Route::get('/asset-returns/create', [AssetReturnController::class, 'create'])
    ->name('asset-returns.create');

Route::post('/asset-returns', [AssetReturnController::class, 'store'])
    ->name('asset-returns.store');

    Route::get(
    '/disposal-containers',
    [DisposalContainerController::class, 'index']
)->name('disposal-containers.index');

Route::post(
    '/disposal-containers',
    [DisposalContainerController::class, 'store']
)->name('disposal-containers.store');

Route::get(
    '/disposal-containers/{container}',
    [DisposalContainerController::class, 'show']
)
    ->whereNumber('container')
    ->name('disposal-containers.show');

Route::post(
    '/disposal-containers/{container}/assets',
    [DisposalContainerController::class, 'addAsset']
)
    ->whereNumber('container')
    ->name('disposal-containers.assets.store');

    Route::post(
    '/disposal-containers/{container}/external-materials',
    [DisposalContainerController::class, 'addExternal']
)
    ->whereNumber('container')
    ->name('disposal-containers.external.store');

    Route::post(
    '/disposal-containers/{container}/close',
    [DisposalContainerController::class, 'close']
)
    ->whereNumber('container')
    ->name('disposal-containers.close');
});