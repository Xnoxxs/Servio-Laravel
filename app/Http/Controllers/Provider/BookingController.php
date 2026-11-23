<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        return view('bookings.index', [
            'providerView' => true,
            'bookings' => $request->user()->providerBookings()
                ->with(['service', 'customer'])
                ->orderByRaw('case when bookings.status = ? then 0 else 1 end', [BookingStatus::Pending->value])
                ->latest('bookings.id')
                ->paginate(12),
        ]);
    }

    public function show(Booking $booking): View
    {
        Gate::authorize('manage', $booking);

        return view('bookings.show', ['booking' => $booking->load(['service', 'customer']), 'providerView' => true]);
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
