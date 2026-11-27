<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Shared\Controller;
use App\Models\Booking;
use App\Models\BookingStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CustomerBookingController extends Controller
{
    public function index(Request $request): View
    {
        $bookings = $request->user()->customerBookings()
            ->with('service')
            ->withExists('review')
            ->latest('id')
            ->paginate(12);
        $providerView = false;

        return view('bookings.index', compact('bookings', 'providerView'));
    }

    public function show(Booking $booking): View
    {
        Gate::authorize('view', $booking);

        $booking->load(['service.provider', 'review']);
        $providerView = false;

        return view('bookings.show', compact('booking', 'providerView'));
    }

    public function cancel(Booking $booking): RedirectResponse
    {
        Gate::authorize('cancel', $booking);
        $booking->transitionTo(BookingStatus::Cancelled);

        return to_route('bookings.show', $booking)->with('status', 'Booking cancelled.');
    }
}
