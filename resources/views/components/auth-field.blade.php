@props(['name', 'label', 'type' => 'text', 'autocomplete' => null])

@php($previousValue = $type === 'password' ? null : old($name))

<div>
    <label for="{{ $name }}" class="block text-sm font-medium">{{ $label }}</label>
    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        autocomplete="{{ $autocomplete }}"
        @if (is_scalar($previousValue)) value="{{ $previousValue }}" @endif
        @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror
        required
        class="mt-1.5 block w-full rounded-lg border border-stone-300 px-3 py-2.5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-700"
    >
    @error($name)
        <p id="{{ $name }}-error" class="mt-1.5 text-sm text-red-700">{{ $message }}</p>
    @enderror
</div>
