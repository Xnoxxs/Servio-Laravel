<x-app-layout title="Booking details">
    <div class="mx-auto max-w-3xl px-5 py-10 sm:px-8 sm:py-14">
        <a href="{{ route($providerView ? 'provider.bookings.index' : 'bookings.index') }}" class="text-sm font-semibold text-emerald-900 underline">{{ $providerView ? 'Back to booking requests' : 'Back to my bookings' }}</a>
        <h1 class="mt-4 text-3xl font-semibold tracking-tight sm:text-4xl">Booking details</h1>

        @if (session('status'))
            <p role="status" class="mt-5 rounded-xl bg-emerald-100 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</p>
        @endif

        @error('booking')
            <p role="alert" class="mt-5 rounded-xl bg-red-100 px-4 py-3 text-sm text-red-900">{{ $message }}</p>
        @enderror

        <section class="mt-6 rounded-2xl border border-stone-200 bg-white p-5 sm:p-8">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="break-words text-2xl font-semibold">{{ $booking->service->title }}</h2>
                <x-booking-status :status="$booking->status" />
            </div>
            <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-sm text-stone-600">Appointment</dt>
                    <dd class="font-semibold">{{ $booking->scheduled_at->clone()->setTimezone(\App\Models\Booking::APPOINTMENT_TIMEZONE)->format('j M Y, H:i T') }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-stone-600">{{ $providerView ? 'Customer' : 'Provider' }}</dt>
                    <dd class="font-semibold">{{ $providerView ? $booking->customer->name : $booking->service->provider->name }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-stone-600">Duration</dt>
                    <dd class="font-semibold">{{ $booking->duration_minutes }} min</dd>
                </div>
                <div>
                    <dt class="text-sm text-stone-600">Price at request</dt>
                    <dd class="font-semibold text-emerald-900">€{{ number_format($booking->price_cents / 100, 2) }}</dd>
                </div>
            </dl>
        </section>

        <div class="mt-5 flex flex-wrap gap-3">
            @if ($providerView)
                @if ($booking->canTransitionTo(\App\Models\BookingStatus::Accepted))
                    <x-booking-action :action="route('provider.bookings.accept', $booking)">Accept</x-booking-action>
                @endif
                @if ($booking->canTransitionTo(\App\Models\BookingStatus::Rejected))
                    <x-booking-action :action="route('provider.bookings.reject', $booking)">Reject</x-booking-action>
                @endif
                @if ($booking->canTransitionTo(\App\Models\BookingStatus::Completed))
                    <x-booking-action :action="route('provider.bookings.complete', $booking)">Mark completed</x-booking-action>
                @endif
            @elseif ($booking->canTransitionTo(\App\Models\BookingStatus::Cancelled))
                <x-booking-action :action="route('bookings.cancel', $booking)">Cancel booking</x-booking-action>
            @endif
        </div>
        @unless ($providerView)
            @can('review', $booking)
                <section class="mt-6 rounded-2xl border border-stone-200 bg-white p-5 sm:p-8" aria-labelledby="booking-review-heading">
                    @if ($booking->review)
                        <h2 id="booking-review-heading" class="text-xl font-semibold">Your review</h2>
                        <p class="mt-2 text-sm font-semibold text-emerald-900">{{ $booking->review->rating }} out of 5 stars</p>
                        @if ($booking->review->comment)
                            <p class="mt-2 whitespace-pre-line break-words text-stone-700">{{ $booking->review->comment }}</p>
                        @endif
                    @else
                        <h2 id="booking-review-heading" class="text-xl font-semibold">Leave a review</h2>
                        <form method="POST" action="{{ route('bookings.reviews.store', $booking) }}" class="mt-4 space-y-4">
                            @csrf
                            <div>
                                <label for="rating" class="block text-sm font-semibold">Rating</label>
                                <select id="rating" name="rating" required class="mt-2 w-full rounded-xl border border-stone-300 bg-white px-4 py-2.5 sm:max-w-xs"
                                    @error('rating') aria-invalid="true" aria-describedby="rating-error" @enderror>
                                    <option value="">Choose a rating</option>
                                    @for ($rating = 5; $rating >= 1; $rating--)
                                        <option value="{{ $rating }}" @selected(is_scalar(old('rating')) && (string) old('rating') === (string) $rating)>{{ $rating }} {{ $rating === 1 ? 'star' : 'stars' }}</option>
                                    @endfor
                                </select>
                                @error('rating')
                                    <p id="rating-error" class="mt-1 text-sm text-red-700">{{ $message }}</p>
                                @enderror
                            </div>
                            <x-service-field name="comment" label="Comment (optional)" :multiline="true" rows="4" maxlength="2000" />
                            <x-button>Post review</x-button>
                        </form>
                    @endif
                </section>
            @endcan
        @endunless
    </div>
</x-app-layout>
