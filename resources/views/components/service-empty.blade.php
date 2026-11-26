@props(['title', 'message'])

<div class="rounded-2xl border border-dashed border-stone-300 bg-white px-6 py-12 text-center">
    <h3 class="text-lg font-semibold">{{ $title }}</h3>
    <p class="mx-auto mt-2 max-w-md text-sm text-stone-600">{{ $message }}</p>
    @if ($slot->isNotEmpty())
        <div class="mt-5">{{ $slot }}</div>
    @endif
</div>
