<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingReviewRequest;
use App\Models\Booking;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;

class BookingReviewController extends Controller
{
    public function store(StoreBookingReviewRequest $request, Booking $booking): RedirectResponse
    {
        try {
            $booking->review()->create($request->validated());
        } catch (UniqueConstraintViolationException) {
            return to_route('bookings.show', $booking)->withErrors(['booking' => 'You already reviewed this booking.']);
        }

        return to_route('bookings.show', $booking)->with('status', 'Review posted.');
    }
}
