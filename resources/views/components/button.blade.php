@props(['href' => null])

@php
    $classes = 'inline-flex min-h-10 items-center justify-center rounded-full bg-[#174937] px-5 py-2 text-sm font-semibold text-white hover:bg-[#24654d] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#174937]';
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->class($classes)->merge(['type' => 'submit']) }}>{{ $slot }}</button>
@endif
