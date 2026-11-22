@props(['name', 'label', 'value' => null, 'multiline' => false])

<div>
    <label for="{{ $name }}" class="block text-sm font-semibold">{{ $label }}</label>
    @php
        $previousValue = old($name, $value);
        $displayValue = is_scalar($previousValue) ? $previousValue : '';
        $fieldAttributes = $attributes->class('mt-2 block w-full rounded-xl border border-stone-300 bg-white px-4 py-2.5 focus-visible:outline-2 focus-visible:outline-emerald-800');
    @endphp
    @if ($multiline)
        <textarea id="{{ $name }}" name="{{ $name }}" {{ $fieldAttributes }}
            @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror>{{ $displayValue }}</textarea>
    @else
        <input id="{{ $name }}" name="{{ $name }}" value="{{ $displayValue }}" {{ $fieldAttributes }}
            @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror>
    @endif
    @error($name)
        <p id="{{ $name }}-error" class="mt-1 text-sm text-red-700">{{ $message }}</p>
    @enderror
</div>
