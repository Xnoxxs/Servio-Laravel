<x-app-layout :title="$providerView ? 'Booking requests' : 'My bookings'">
    <div class="mx-auto max-w-7xl px-5 py-10 sm:px-8 sm:py-14">
        <h1 class="text-3xl font-semibold tracking-tight sm:text-5xl">{{ $providerView ? 'Booking requests' : 'My bookings' }}</h1>
        <div class="mt-8">
            @if ($bookings->isEmpty())
                <x-service-empty title="No bookings on this page" :message="$providerView ? 'Requests for your services will appear here.' : 'Browse the marketplace to request a service.'">
                    @if (! $providerView)
                        <x-button :href="route('services.index')">Browse services</x-button>
                    @endif
                </x-service-empty>
            @else
                <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($bookings as $booking)
                        <li class="rounded-2xl border border-stone-200 bg-white p-5">
                            <div class="flex flex-wrap items-center gap-2">
                                <x-booking-status :status="$booking->status" />
                                @if (! $providerView && ! $booking->review_exists)
                                    @can('review', $booking)
                                        <a href="{{ route('bookings.show', $booking) }}#booking-review-heading" class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-900 hover:underline">Leave a review</a>
                                    @endcan
                                @endif
                            </div>
                            <h2 class="mt-3 break-words text-xl font-semibold">
                                <a href="{{ route($providerView ? 'provider.bookings.show' : 'bookings.show', $booking) }}" class="underline decoration-stone-300 underline-offset-4 hover:decoration-emerald-800">{{ $booking->service->title }}</a>
                            </h2>
                            <p class="mt-2 text-sm text-stone-700">{{ $booking->scheduled_at->clone()->setTimezone(\App\Models\Booking::APPOINTMENT_TIMEZONE)->format('j M Y, H:i T') }}</p>
                            @if ($providerView)
                                <p class="mt-1 text-sm text-stone-700">{{ $booking->customer->name }}</p>
                            @endif
                            <p class="mt-2 font-semibold text-emerald-900">€{{ number_format($booking->price_cents / 100, 2) }}</p>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-6">{{ $bookings->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
