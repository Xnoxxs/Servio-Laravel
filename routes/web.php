<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\Provider\ServiceController;
use Illuminate\Support\Facades\Route;

Route::get('/', [MarketplaceController::class, 'home'])->name('home');
Route::get('/services', [MarketplaceController::class, 'index'])->name('services.index');
Route::get('/services/{service}', [MarketplaceController::class, 'show'])->whereNumber('service')->name('services.show');

Route::middleware('guest')->group(function (): void {
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:10,1,register')
        ->name('register.store');

    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::view('admin', 'admin.index')
    ->middleware(['auth', 'admin'])
    ->name('admin.dashboard');

Route::middleware('auth')->prefix('provider')->name('provider.')->group(function (): void {
    Route::get('/', [ServiceController::class, 'index'])->name('dashboard');
    Route::patch('services/{service}/publication', [ServiceController::class, 'updatePublication'])->name('services.publication');
    Route::resource('services', ServiceController::class)->except(['index', 'show']);
});
