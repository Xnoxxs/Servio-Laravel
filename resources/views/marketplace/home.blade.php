<x-app-layout title="Local services">
    <div class="mx-auto max-w-7xl px-5 py-8 sm:px-8 sm:py-12">
        <section class="relative isolate overflow-hidden rounded-[2rem] bg-[#174937] px-6 py-12 text-white sm:px-12 sm:py-20 lg:px-16">
            <div aria-hidden="true" class="absolute -right-24 -top-16 -z-10 size-80 rounded-full border-[3rem] border-white/10 sm:size-[28rem]"></div>
            <div aria-hidden="true" class="absolute -bottom-40 right-8 -z-10 size-80 rounded-full bg-[#28634d]"></div>
            <h1 class="max-w-xl text-4xl font-semibold leading-tight tracking-tight sm:text-6xl">Find local help for the jobs you need done</h1>
            <p class="mt-5 max-w-md text-base leading-relaxed text-emerald-50 sm:text-lg">Browse services from local providers, from home cleaning to garden care.</p>
            <a href="{{ route('services.index') }}" class="mt-7 inline-flex rounded-full bg-[#e5f6a3] px-6 py-3 text-sm font-bold text-[#174937] hover:bg-white focus-visible:outline-2 focus-visible:outline-offset-4">Explore services</a>
        </section>

        <section class="mt-12" aria-labelledby="categories">
            <h2 id="categories" class="text-2xl font-semibold tracking-tight sm:text-3xl">Browse by category</h2>
            @if ($categories->isEmpty())
                <p class="mt-4 text-stone-600">Categories will appear as the catalogue is set up.</p>
            @else
                <ul class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                    @foreach ($categories as $category)
                        <li>
                            <a href="{{ route('services.index', ['category' => $category->slug]) }}" class="flex min-h-16 items-center rounded-xl border border-stone-200 bg-white px-4 text-sm font-semibold hover:border-emerald-800 hover:text-emerald-800">
                                {{ $category->name }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="mt-12" aria-labelledby="featured">
            <div class="mb-5 flex flex-wrap items-baseline justify-between gap-3">
                <h2 id="featured" class="text-2xl font-semibold tracking-tight sm:text-3xl">Featured services</h2>
                <a href="{{ route('services.index') }}" class="text-sm font-semibold text-[#174937] underline underline-offset-4">View all services</a>
            </div>
            @if ($featuredServices->isEmpty())
                <x-marketplace-empty title="No services yet" message="Published listings will appear here when providers add them." />
            @else
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($featuredServices as $service)
                        <x-service-card :service="$service" />
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</x-app-layout>
