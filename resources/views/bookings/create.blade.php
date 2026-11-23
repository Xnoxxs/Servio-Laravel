<x-app-layout title="Request a booking">
    <div class="mx-auto max-w-3xl px-5 py-10 sm:px-8 sm:py-14">
        <a href="{{ route('services.show', $service) }}" class="text-sm font-semibold text-emerald-900 underline">Back to service</a>
        <h1 class="mt-4 text-3xl font-semibold tracking-tight sm:text-4xl">Request a booking</h1>
        <p class="mt-2 break-words text-stone-700">{{ $service->title }} · {{ $service->provider->name }}</p>
        <p class="mt-1 font-semibold text-emerald-900">€{{ number_format($service->price_cents / 100, 2) }} · {{ $service->duration_minutes }} min</p>

        <form method="POST" action="{{ route('bookings.store', $service) }}" class="mt-6 space-y-5 rounded-2xl border border-stone-200 bg-white p-5 sm:p-8">
            @csrf
            <x-service-field name="scheduled_at" label="Appointment date and time" type="datetime-local" required />
            <p class="text-sm text-stone-600">Choose a future time in Europe/Madrid. Your request stays pending until the provider responds.</p>
            <x-button>Send request</x-button>
        </form>
    </div>
</x-app-layout>
