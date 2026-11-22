<x-app-layout :title="$service->title">
    <div class="mx-auto max-w-7xl px-5 py-10 sm:px-8 sm:py-14">
        <a href="{{ route('services.index', ['category' => $service->category->slug]) }}" class="text-sm font-semibold text-[#174937] underline underline-offset-4">{{ $service->category->name }}</a>
        <h1 class="mt-3 text-3xl font-semibold tracking-tight sm:text-5xl">{{ $service->title }}</h1>

        <div class="mt-8 grid items-start gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
            <div class="space-y-6">
                @if ($service->image_path)
                    <img src="{{ Storage::disk('public')->url($service->image_path) }}" alt="{{ $service->title }}" class="aspect-video w-full rounded-2xl object-cover">
                @endif
                <section class="rounded-2xl border border-stone-200 bg-white p-6">
                    <h2 class="text-xl font-semibold">About this service</h2>
                    <p class="mt-3 whitespace-pre-line break-words leading-relaxed text-stone-700">{{ $service->description }}</p>
                </section>
                <section class="rounded-2xl border border-stone-200 bg-white p-6">
                    <h2 class="text-xl font-semibold">Customer reviews</h2>
                    <ul class="mt-3 divide-y divide-stone-200">
                        @forelse ($reviews as $review)
                            <li class="py-3">
                                <p class="font-semibold">{{ $review->booking->customer->name }} · {{ $review->rating }} out of 5</p>
                                @if ($review->comment)
                                    <p class="mt-1 break-words text-stone-700">{{ $review->comment }}</p>
                                @endif
                            </li>
                        @empty
                            <li class="pt-2 text-sm text-stone-600">No reviews yet.</li>
                        @endforelse
                    </ul>
                    {{ $reviews->links() }}
                </section>
            </div>

            <aside class="rounded-2xl border border-stone-200 bg-white p-6" aria-label="Service information">
                <dl class="space-y-4">
                    <div>
                        <dt class="text-sm text-stone-600">Price</dt>
                        <dd class="text-3xl font-semibold text-[#174937]">€{{ number_format($service->price_cents / 100, 2) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-stone-600">Provider</dt>
                        <dd class="font-semibold">{{ $service->provider->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-stone-600">Duration</dt>
                        <dd class="font-semibold">{{ $service->duration_minutes ? $service->duration_minutes.' min' : 'Duration unavailable' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-stone-600">Location</dt>
                        <dd class="font-semibold">{{ $service->location ?? 'Location unavailable' }}</dd>
                    </div>
                </dl>
            </aside>
        </div>
    </div>
</x-app-layout>
