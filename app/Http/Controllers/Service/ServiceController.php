<?php

namespace App\Http\Controllers\Service;

use App\Http\Controllers\Shared\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\BookingStatus;
use App\Models\Category;
use App\Models\Service;
use App\Notifications\BookingStatusNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(Request $request): View
    {
        // Read the category URL parameter, such as gardening.
        $categorySlug = $request->query('category');
        // Read the search term. Default to an empty string.
        $keyword = $request->query('q', '');
        // Return HTTP 422 if the search term is not a string or exceeds 100 characters.
        abort_unless(
            is_string($keyword) && mb_strlen($keyword) <= 100, 422, 'Search must be 100 characters or fewer.'
        );

        $keyword = trim($keyword); // Remove leading and trailing whitespace.
        // Escape literal !, % and _ in the input. The outer % signs allow matches anywhere in the text.
        $pattern = '%'.strtr($keyword, ['!' => '!!', '%' => '!%', '_' => '!_']).'%';
        abort_unless($categorySlug === null || is_string($categorySlug), 404); // Reject category inputs such as arrays.

        $category = $categorySlug === null // Check whether the visitor selected a category.
            ? null // Leave the category filter unset.
            : Category::where('slug', $categorySlug)->firstOrFail(); // Find the selected category or return HTTP 404.

        // Keep the active filters on pagination links and omit empty values.
        $filters = array_filter([
            'category' => $category?->slug,
            'q' => $keyword,
        ], fn (?string $value): bool => $value !== null && $value !== '');

        $services = Service::query()->forPublicBrowsing()
            ->when($category, fn (Builder $query): Builder => $query->whereBelongsTo($category))
            // Group title and description so either can match within the other filters.
            ->when($keyword !== '', fn (Builder $query): Builder => $query->where(
                fn (Builder $query): Builder => $query
                    ->whereRaw("title LIKE ? ESCAPE '!'", [$pattern])
                    ->orWhereRaw("description LIKE ? ESCAPE '!'", [$pattern]),
            ))
            ->paginate(9)
            ->appends($filters);

        $categories = Category::orderBy('name')->get();

        return view('service.index', compact('services', 'categories', 'category', 'keyword'));
    }

    public function show(string $service): View
    {
        $service = Service::query()->forPublicBrowsing()->findOrFail($service);
        // {"id":42,"title":"Window cleaning"}
        // Get review of service 42
        $reviews = $service->reviews()
            ->where('reviews.is_hidden', false)
            ->with('booking.customer')
            ->latest('reviews.id')
            ->paginate(5);

        return view('service.show', compact('service', 'reviews'));
    }

    public function createBooking(Service $service): View
    {
        Gate::authorize('create', [Booking::class, $service]);
        abort_unless($service->duration_minutes > 0, 409, 'This service needs a duration before it can be booked.');

        $service->load('provider');

        return view('bookings.create', compact('service'));
    }

    public function makeBooking(StoreBookingRequest $request, Service $service): RedirectResponse
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
            BookingStatusNotification::dispatchAfterCommit($booking, BookingStatus::Pending);

            return $booking;
        }, attempts: 3);

        return to_route('bookings.show', $booking)->with('status', 'Booking requested.');
    }
}
