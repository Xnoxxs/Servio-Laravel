<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Shared\Controller;
use App\Models\Booking;
use App\Models\BookingStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class BookingRequestController extends Controller
{
    public function index(Request $request): View
    {
        $bookings = $request->user()->providerBookings()
            ->with(['service', 'customer'])
            ->orderByRaw('case when bookings.status = ? then 0 else 1 end', [BookingStatus::Pending->value])
            ->latest('bookings.id')
            ->paginate(12);
        $providerView = true;

        return view('bookings.index', compact('bookings', 'providerView'));
    }

    public function show(Booking $booking): View
    {
        Gate::authorize('manage', $booking);

        $booking->load(['service', 'customer']);
        $providerView = true;

        return view('bookings.show', compact('booking', 'providerView'));
    }

    public function accept(Booking $booking): RedirectResponse
    {
        return $this->decide($booking, BookingStatus::Accepted);
    }

    public function reject(Booking $booking): RedirectResponse
    {
        return $this->decide($booking, BookingStatus::Rejected);
    }

    public function complete(Booking $booking): RedirectResponse
    {
        return $this->decide($booking, BookingStatus::Completed);
    }

    private function decide(Booking $booking, BookingStatus $status): RedirectResponse
    {
        Gate::authorize('manage', $booking);
        $booking->transitionTo($status);

        return to_route('provider.bookings.show', $booking)->with('status', 'Booking '.$status->value.'.');
    }
}
