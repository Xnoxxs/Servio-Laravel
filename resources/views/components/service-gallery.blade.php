@props(['photos', 'label', 'href' => null])

@php($multiple = count($photos) > 1)

<div {{ $attributes->class('relative overflow-hidden bg-[#e3ebdb]')->merge(['data-service-gallery' => '']) }}>
    @if ($photos !== [])
        <div data-gallery-track class="flex h-full w-full snap-x snap-mandatory overflow-x-auto [scrollbar-width:none] focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-emerald-800"
            @if ($multiple) tabindex="0" aria-label="{{ $label }} photos" @endif>
            @foreach ($photos as $photo)
                <div class="h-full w-full shrink-0 snap-start">
                    @if ($href)
                        <a href="{{ $href }}" tabindex="-1" aria-hidden="true" class="block h-full">
                            <img src="{{ $photo }}" alt="" loading="lazy" class="h-full w-full object-cover">
                        </a>
                    @else
                        <img src="{{ $photo }}" alt="{{ $label }} — photo {{ $loop->iteration }}" class="h-full w-full object-cover">
                    @endif
                </div>
            @endforeach
        </div>
        @if ($multiple)
            <div data-gallery-controls hidden>
                <button type="button" data-gallery-step="-1" aria-label="Previous photo of {{ $label }}"
                    class="absolute left-3 top-1/2 z-20 grid size-10 -translate-y-1/2 cursor-pointer place-items-center rounded-full bg-white text-3xl leading-none font-semibold text-[#174937] shadow-sm hover:bg-emerald-50">‹</button>
                <button type="button" data-gallery-step="1" aria-label="Next photo of {{ $label }}"
                    class="absolute right-3 top-1/2 z-20 grid size-10 -translate-y-1/2 cursor-pointer place-items-center rounded-full bg-white text-3xl leading-none font-semibold text-[#174937] shadow-sm hover:bg-emerald-50">›</button>
                <span data-gallery-counter role="status" class="pointer-events-none absolute bottom-4 right-4 z-20 rounded-full bg-stone-950/75 px-3 py-1 text-xs font-semibold text-white">1 / {{ count($photos) }}</span>
            </div>
        @endif
    @elseif (! $href)
        <p class="grid h-full place-items-center text-sm font-medium text-[#174937]">No photos available</p>
    @endif

    {{ $slot }}
</div>
