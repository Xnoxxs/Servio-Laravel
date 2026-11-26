<x-app-layout :title="$category?->name ?? 'Browse services'">
    <div class="mx-auto max-w-7xl px-5 py-10 sm:px-8 sm:py-14">
        <h1 class="text-3xl font-semibold tracking-tight sm:text-5xl">{{ $category?->name ?? 'Browse services' }}</h1>
        <form method="GET" action="{{ route('services.index') }}" class="mt-6 max-w-xl">
            @if ($category)
                <input type="hidden" name="category" value="{{ $category->slug }}">
            @endif
            <label for="keyword" class="block text-sm font-semibold">Search services</label>
            <div class="mt-2 flex flex-wrap gap-2 sm:flex-nowrap">
                <input id="keyword" type="search" name="q" value="{{ $keyword }}" maxlength="100"
                    placeholder="Try garden cleanup" class="min-w-0 flex-1 rounded-xl border border-stone-300 bg-white px-4 py-2 text-sm focus-visible:outline-2 focus-visible:outline-emerald-800">
                <x-button>Search</x-button>
            </div>
        </form>
        <nav aria-label="Service categories" class="mt-6 flex flex-wrap gap-2">
            <a href="{{ route('services.index', $keyword !== '' ? ['q' => $keyword] : []) }}" @if (!$category) aria-current="page" @endif
                class="rounded-full border border-stone-300 bg-white px-4 py-2 text-sm font-semibold hover:border-emerald-800 aria-[current=page]:border-[#174937] aria-[current=page]:bg-[#174937] aria-[current=page]:text-white">All categories</a>
            @foreach ($categories as $option)
                <a href="{{ route('services.index', ['category' => $option->slug] + ($keyword !== '' ? ['q' => $keyword] : [])) }}" @if ($category?->is($option)) aria-current="page" @endif
                    class="rounded-full border border-stone-300 bg-white px-4 py-2 text-sm font-semibold hover:border-emerald-800 aria-[current=page]:border-[#174937] aria-[current=page]:bg-[#174937] aria-[current=page]:text-white">{{ $option->name }}</a>
            @endforeach
        </nav>

        <section class="mt-8" aria-label="Service results">
            @if ($services->isEmpty())
                <x-service-empty title="No services found" message="Try a different keyword or browse all categories.">
                    @if ($category || $keyword !== '' || $services->currentPage() > 1)
                        <x-button :href="route('services.index')">View all services</x-button>
                    @endif
                </x-service-empty>
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
