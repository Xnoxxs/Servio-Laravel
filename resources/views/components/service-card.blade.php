@props(['service'])

<article {{ $attributes->class('flex h-full flex-col overflow-hidden rounded-2xl border border-stone-200 bg-white') }}>
    <div class="relative flex aspect-[16/9] items-end overflow-hidden bg-[#e3ebdb] p-5">
        @if ($service->image_path)
            <img src="{{ Storage::disk('public')->url($service->image_path) }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover">
        @endif
        <span class="relative rounded-full bg-white px-3 py-1 text-xs font-semibold text-[#174937]">{{ $service->category->name }}</span>
    </div>
    <div class="flex flex-1 flex-col gap-3 p-5">
        <h3 class="text-xl font-semibold tracking-tight">
            @if (Route::has('services.show'))
                <a href="{{ route('services.show', $service) }}" class="hover:underline">{{ $service->title }}</a>
            @else
                {{ $service->title }}
            @endif
        </h3>
        <p class="line-clamp-2 text-sm leading-relaxed text-stone-600">{{ $service->description }}</p>
        <div class="mt-auto flex flex-wrap items-baseline justify-between gap-2 border-t border-stone-100 pt-3 text-sm">
            <span class="text-stone-600">{{ $service->provider->name }}</span>
            <span class="font-semibold text-[#174937]">€{{ number_format($service->price_cents / 100, 2) }}</span>
        </div>
    </div>
</article>
