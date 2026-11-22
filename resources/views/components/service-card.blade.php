@props(['service'])

<article {{ $attributes->class('relative isolate flex h-full flex-col overflow-hidden rounded-2xl border border-stone-200 bg-white') }}>
    <x-service-gallery :photos="$service->photoUrls()" :label="$service->title" :href="route('services.show', $service)" :class="$service->image_path ? 'z-20 aspect-video' : 'aspect-video'">
        <span class="pointer-events-none absolute bottom-4 left-4 z-10 rounded-full bg-white px-3 py-1 text-xs font-semibold text-[#174937]">{{ $service->category->name }}</span>
    </x-service-gallery>
    <div class="flex flex-1 flex-col gap-3 p-5">
        <h3 class="text-xl font-semibold tracking-tight">
            <a href="{{ route('services.show', $service) }}" class="hover:underline after:absolute after:inset-0 after:z-10 focus-visible:after:ring-2 focus-visible:after:ring-inset focus-visible:after:ring-emerald-800">{{ $service->title }}</a>
        </h3>
        <p class="line-clamp-2 text-sm leading-relaxed text-stone-600">{{ $service->description }}</p>
        <div class="mt-auto flex flex-wrap items-baseline justify-between gap-2 border-t border-stone-100 pt-3 text-sm">
            <span class="text-stone-600">{{ $service->provider->name }}</span>
            <span class="font-semibold text-[#174937]">€{{ number_format($service->price_cents / 100, 2) }}</span>
        </div>
    </div>
</article>
