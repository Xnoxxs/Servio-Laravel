<x-app-layout title="Page unavailable">
    <div class="mx-auto max-w-3xl px-5 py-16 sm:px-8">
        <x-service-empty title="This page is unavailable" message="The link may be outdated or the listing may have been taken offline.">
            <x-button :href="route('services.index')">Browse available services</x-button>
        </x-service-empty>
    </div>
</x-app-layout>
