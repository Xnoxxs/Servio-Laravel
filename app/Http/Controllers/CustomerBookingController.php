<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\BookingStatus;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CustomerBookingController extends Controller
{
    public function index(Request $request): View
    {
        return view('bookings.index', [
            'providerView' => false,
            'bookings' => $request->user()->customerBookings()->with('service')->latest('id')->paginate(12),
        ]);
    }

    public function create(Service $service): View
    {
        Gate::authorize('create', [Booking::class, $service]);
        abort_unless($service->duration_minutes > 0, 409, 'This service needs a duration before it can be booked.');

        return view('bookings.create', ['service' => $service->load('provider')]);
    }

    public function store(StoreBookingRequest $request, Service $service): RedirectResponse
    {
        $booking = DB::transaction(function () use ($request, $service): Booking {
            $listing = Service::query()->lockForUpdate()->findOrFail($service->id);
            Gate::authorize('create', [Booking::class, $listing]);

            if (! $request->appointment()->isFuture() || $listing->duration_minutes < 1) {
                throw ValidationException::withMessages([
                    'scheduled_at' => 'Choose a future time for a service with a valid duration.',
                ]);
            }

            $booking = $request->user()->customerBookings()->make(['scheduled_at' => $request->appointment()]);
            $booking->service()->associate($listing);
            $booking->price_cents = $listing->price_cents;
            $booking->duration_minutes = $listing->duration_minutes;
            $booking->currency = 'EUR';
            $booking->status = BookingStatus::Pending;
            $booking->save();

            return $booking;
        }, attempts: 3);

        return to_route('bookings.show', $booking)->with('status', 'Booking requested.');
    }

    public function show(Booking $booking): View
    {
        Gate::authorize('view', $booking);

        return view('bookings.show', ['booking' => $booking->load('service.provider'), 'providerView' => false]);
    }

    public function cancel(Booking $booking): RedirectResponse
    {
        Gate::authorize('cancel', $booking);
        $booking->transitionTo(BookingStatus::Cancelled);

        return to_route('bookings.show', $booking)->with('status', 'Booking cancelled.');
    }
}
