<x-app-layout :title="$category?->name ?? 'Browse services'">
    <div class="mx-auto max-w-7xl px-5 py-10 sm:px-8 sm:py-14">
        <h1 class="text-3xl font-semibold tracking-tight sm:text-5xl">{{ $category?->name ?? 'Browse services' }}</h1>
        <nav aria-label="Service categories" class="mt-6 flex flex-wrap gap-2">
            <a href="{{ route('services.index') }}" @if (!$category) aria-current="page" @endif
                class="rounded-full border border-stone-300 bg-white px-4 py-2 text-sm font-semibold hover:border-emerald-800 aria-[current=page]:border-[#174937] aria-[current=page]:bg-[#174937] aria-[current=page]:text-white">All categories</a>
            @foreach ($categories as $option)
                <a href="{{ route('services.index', ['category' => $option->slug]) }}" @if ($category?->is($option)) aria-current="page" @endif
                    class="rounded-full border border-stone-300 bg-white px-4 py-2 text-sm font-semibold hover:border-emerald-800 aria-[current=page]:border-[#174937] aria-[current=page]:bg-[#174937] aria-[current=page]:text-white">{{ $option->name }}</a>
            @endforeach
        </nav>

        <section class="mt-8" aria-label="Service results">
            @if ($services->isEmpty())
                <x-marketplace-empty title="No services found" message="Try browsing all categories to find available services.">
                    @if ($category || $services->currentPage() > 1)
                        <x-button :href="route('services.index')">View all services</x-button>
                    @endif
                </x-marketplace-empty>
            @else
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($services as $service)
                        <x-service-card :service="$service" />
                    @endforeach
                </div>
                @if ($services->hasPages())
                    <div class="mt-8">{{ $services->links() }}</div>
                @endif
            @endif
        </section>
    </div>
</x-app-layout>
