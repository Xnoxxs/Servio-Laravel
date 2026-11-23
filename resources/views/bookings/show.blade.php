<x-app-layout title="Booking details">
    <div class="mx-auto max-w-3xl px-5 py-10 sm:px-8 sm:py-14">
        <a href="{{ route('bookings.index') }}" class="text-sm font-semibold text-emerald-900 underline">Back to my bookings</a>
        <h1 class="mt-4 text-3xl font-semibold tracking-tight sm:text-4xl">Booking details</h1>

        @if (session('status'))
            <p role="status" class="mt-5 rounded-xl bg-emerald-100 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</p>
        @endif

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
                    <dt class="text-sm text-stone-600">Provider</dt>
                    <dd class="font-semibold">{{ $booking->service->provider->name }}</dd>
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
    </div>
</x-app-layout>
