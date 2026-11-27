<?php

use App\Http\Controllers\Auth\LogInController;
use App\Http\Controllers\Auth\SignInController;
use App\Http\Controllers\Booking\CustomerBookingController;
use App\Http\Controllers\Home\HomeController;
use App\Http\Controllers\Provider\BookingRequestController;
use App\Http\Controllers\Provider\ServiceController as ProviderServiceController;
use App\Http\Controllers\Review\BookingReviewController;
use App\Http\Controllers\Service\ServiceController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/services', [ServiceController::class, 'index'])->name('services.index');

Route::get('/services/{service}', [ServiceController::class, 'show'])->whereNumber('service')->name('services.show');

Route::middleware('guest')->group(function (): void {
    Route::get('signIn', [SignInController::class, 'showSignIn'])->name('signIn');
    Route::post('signIn', [SignInController::class, 'signIn'])
        ->middleware('throttle:10,1,register')
        ->name('signIn.store');

    Route::get('logIn', [LogInController::class, 'create'])->name('login');
    Route::post('logIn', [LogInController::class, 'store'])->name('login.store');
});

Route::post('logout', [LogInController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::view('admin', 'admin.index')
    ->middleware(['auth', 'admin'])
    ->name('admin.dashboard');

Route::middleware('auth')->prefix('provider')->name('provider.')->group(function (): void {
    Route::get('/', [ProviderServiceController::class, 'index'])->name('dashboard');
    Route::patch('services/{service}/publication', [ProviderServiceController::class, 'updatePublication'])->name('services.publication');
    Route::get('bookings', [BookingRequestController::class, 'index'])->name('bookings.index');
    Route::get('bookings/{booking}', [BookingRequestController::class, 'show'])
        ->whereNumber('booking')->name('bookings.show');
    Route::patch('bookings/{booking}/accept', [BookingRequestController::class, 'accept'])
        ->whereNumber('booking')->name('bookings.accept');
    Route::patch('bookings/{booking}/reject', [BookingRequestController::class, 'reject'])
        ->whereNumber('booking')->name('bookings.reject');
    Route::patch('bookings/{booking}/complete', [BookingRequestController::class, 'complete'])
        ->whereNumber('booking')->name('bookings.complete');
    Route::resource('services', ProviderServiceController::class)->except(['index', 'show']);
});

Route::middleware('auth')->group(function (): void {

    Route::get('services/{service}/bookings/create', [ServiceController::class, 'createBooking'])
        ->whereNumber('service')->name('bookings.create');

    Route::post('services/{service}/bookings', [ServiceController::class, 'makeBooking'])
        ->whereNumber('service')->name('bookings.store');

    Route::get('bookings', [CustomerBookingController::class, 'index'])->name('bookings.index');

    Route::get('bookings/{booking}', [CustomerBookingController::class, 'show'])
        ->whereNumber('booking')->name('bookings.show');

    Route::patch('bookings/{booking}/cancel', [CustomerBookingController::class, 'cancel'])
        ->whereNumber('booking')->name('bookings.cancel');

    Route::post('bookings/{booking}/reviews', [BookingReviewController::class, 'create'])
        ->whereNumber('booking')->name('bookings.reviews.store');
}
);
