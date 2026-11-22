<x-app-layout title="Your services">
    <div class="mx-auto max-w-7xl px-5 py-10 sm:px-8 sm:py-14">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-3xl font-semibold tracking-tight sm:text-5xl">Your services</h1>
            <x-button :href="route('provider.services.create')">New service</x-button>
        </div>

        @if (session('status'))
            <p role="status" class="mt-6 rounded-xl bg-emerald-100 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</p>
        @endif

        <div class="mt-8">
            @if ($services->isEmpty())
                <x-marketplace-empty title="No services on this page" message="Create a service and publish it when it’s ready.">
                    @if ($services->currentPage() > 1)
                        <x-button :href="route('provider.dashboard')">Back to your services</x-button>
                    @endif
                </x-marketplace-empty>
            @else
                <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($services as $service)
                        <li class="flex flex-col gap-4 rounded-2xl border border-stone-200 bg-white p-5">
                            <div>
                                <p class="text-sm text-stone-600">{{ $service->category->name }} · {{ $service->is_published ? 'Published' : 'Unpublished' }}</p>
                                <h2 class="mt-1 break-words text-xl font-semibold">{{ $service->title }}</h2>
                                <p class="mt-2 font-semibold text-[#174937]">€{{ number_format($service->price_cents / 100, 2) }}</p>
                            </div>
                            <div class="mt-auto flex flex-wrap items-center gap-x-4 gap-y-3 border-t border-stone-200 pt-4 text-sm font-semibold">
                                <a href="{{ route('provider.services.edit', $service) }}" class="text-[#174937] underline">Edit</a>
                                @if ($service->is_published)
                                    <a href="{{ route('services.show', $service) }}" class="text-[#174937] underline">View listing</a>
                                @endif
                                <form method="POST" action="{{ route('provider.services.publication', $service) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="is_published" value="{{ $service->is_published ? '0' : '1' }}">
                                    <button type="submit" class="cursor-pointer text-[#174937] underline">{{ $service->is_published ? 'Deactivate' : 'Publish' }}</button>
                                </form>
                                <details>
                                    <summary class="cursor-pointer text-red-700 underline">Delete</summary>
                                    <form method="POST" action="{{ route('provider.services.destroy', $service) }}" class="mt-2">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="cursor-pointer rounded-lg bg-red-700 px-3 py-2 text-white">Confirm deletion</button>
                                    </form>
                                </details>
                            </div>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-6">{{ $services->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
