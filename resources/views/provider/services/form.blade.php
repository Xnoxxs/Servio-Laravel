<x-app-layout :title="$service->exists ? 'Edit service' : 'New service'">
    <div class="mx-auto max-w-3xl px-5 py-10 sm:px-8 sm:py-14">
        <a href="{{ route('provider.dashboard') }}" class="text-sm font-semibold text-[#174937] underline">Back to your services</a>
        <h1 class="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">{{ $service->exists ? 'Edit service' : 'New service' }}</h1>

        @if ($categories->isEmpty())
            <div class="mt-6">
                <x-marketplace-empty title="No categories available" message="A category must be available before you can save a service." />
            </div>
        @else
            <form method="POST" enctype="multipart/form-data" action="{{ $service->exists ? route('provider.services.update', $service) : route('provider.services.store') }}" class="mt-6 space-y-5 rounded-2xl border border-stone-200 bg-white p-5 sm:p-8">
                @csrf
                @if ($service->exists)
                    @method('PUT')
                @endif

                <x-service-field name="title" label="Title" :value="$service->title" maxlength="255" required />

                @php($selectedCategory = filter_var(old('category_id', $service->category_id), FILTER_VALIDATE_INT))
                <div>
                    <label for="category_id" class="block text-sm font-semibold">Category</label>
                    <select id="category_id" name="category_id" required class="mt-2 block w-full rounded-xl border border-stone-300 bg-white px-4 py-2.5"
                        @error('category_id') aria-invalid="true" aria-describedby="category-error" @enderror>
                        <option value="">Choose a category</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected($selectedCategory === $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <p id="category-error" class="mt-1 text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                <x-service-field name="description" label="Description" :value="$service->description" :multiline="true" rows="5" maxlength="5000" required />
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-service-field name="price" label="Price in euros" :value="$service->exists ? number_format($service->price_cents / 100, 2, '.', '') : null" inputmode="decimal" maxlength="10" required />
                    <x-service-field name="duration_minutes" label="Duration in minutes" :value="$service->duration_minutes" type="number" min="1" max="10080" required />
                </div>
                <x-service-field name="location" label="Location" :value="$service->location" maxlength="255" placeholder="City or service area" required />

                <div class="border-t border-stone-200 pt-5">
                    <label for="image" class="block text-sm font-semibold">Cover image</label>
                    <p id="image-help" class="mt-1 text-sm text-stone-600">JPEG, PNG or WebP up to 5 MB and 4096 × 4096 pixels.</p>
                    @if ($service->image_path)
                        <img src="{{ Storage::disk('public')->url($service->image_path) }}" alt="Current service cover" class="mt-3 aspect-video w-full max-w-xs rounded-xl object-cover">
                        <label class="mt-3 flex items-center gap-2 text-sm">
                            <input type="checkbox" name="remove_image" value="1" @checked(old('remove_image')) class="accent-emerald-800">
                            Remove current image
                        </label>
                    @endif
                    <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp"
                        aria-describedby="image-help @error('image') image-error @enderror"
                        @error('image') aria-invalid="true" @enderror
                        class="mt-3 block w-full text-sm file:mr-3 file:rounded-full file:border-0 file:bg-emerald-100 file:px-4 file:py-2 file:font-semibold file:text-emerald-900">
                    @error('image')
                        <p id="image-error" class="mt-1 text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                <x-button>{{ $service->exists ? 'Save changes' : 'Create draft' }}</x-button>
            </form>
        @endif

        @if ($service->exists)
            <details class="mt-6 rounded-2xl border border-stone-200 bg-white p-5 sm:p-8">
                <summary class="cursor-pointer font-semibold text-red-800">Archive service</summary>
                <p class="mt-3 text-sm text-stone-700">This removes the listing from the marketplace and your dashboard. Existing bookings stay available.</p>
                <form method="POST" action="{{ route('provider.services.destroy', $service) }}" class="mt-4">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="cursor-pointer rounded-full bg-red-800 px-5 py-2.5 text-sm font-semibold text-white hover:bg-red-900">Confirm archive</button>
                </form>
            </details>
        @endif
    </div>
</x-app-layout>
