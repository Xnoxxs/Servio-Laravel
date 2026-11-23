<x-app-layout title="My bookings">
    <div class="mx-auto max-w-7xl px-5 py-10 sm:px-8 sm:py-14">
        <h1 class="text-3xl font-semibold tracking-tight sm:text-5xl">My bookings</h1>
        <div class="mt-8">
            @if ($bookings->isEmpty())
                <x-marketplace-empty title="No bookings on this page" message="Browse the marketplace to request a service.">
                    <x-button :href="route('services.index')">Browse services</x-button>
                </x-marketplace-empty>
            @else
                <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($bookings as $booking)
                        <li class="rounded-2xl border border-stone-200 bg-white p-5">
                            <x-booking-status :status="$booking->status" />
                            <h2 class="mt-3 break-words text-xl font-semibold">
                                <a href="{{ route('bookings.show', $booking) }}" class="underline decoration-stone-300 underline-offset-4 hover:decoration-emerald-800">{{ $booking->service->title }}</a>
                            </h2>
                            <p class="mt-2 text-sm text-stone-700">{{ $booking->scheduled_at->clone()->setTimezone(\App\Models\Booking::APPOINTMENT_TIMEZONE)->format('j M Y, H:i T') }}</p>
                            <p class="mt-2 font-semibold text-emerald-900">€{{ number_format($booking->price_cents / 100, 2) }}</p>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-6">{{ $bookings->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
