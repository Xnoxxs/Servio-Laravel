@props(['title'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} | Servio</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-[#f8f8f2] text-stone-900 antialiased">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:m-3 focus:self-start focus:underline">Skip to content</a>
    <header class="border-b border-stone-200 bg-white">
        <nav aria-label="Main navigation" class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-x-6 gap-y-3 px-5 py-4 sm:px-8">
            <a href="{{ route('home') }}" class="text-2xl font-bold tracking-tight text-[#174937]">Servio</a>
            <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm font-semibold">
                <a href="{{ route('services.index') }}" class="hover:text-emerald-800 hover:underline">Browse services</a>
                @auth
                    <a href="{{ route('provider.dashboard') }}" class="hover:text-emerald-800 hover:underline">Provider dashboard</a>
                    @if (auth()->user()->is_admin)
                        <a href="{{ route('admin.dashboard') }}" class="hover:text-emerald-800 hover:underline">Administration</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="cursor-pointer hover:text-emerald-800 hover:underline">Log out</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="hover:text-emerald-800 hover:underline">Log in</a>
                    <x-button :href="route('register')">Sign up</x-button>
                @endauth
            </div>
        </nav>
    </header>

    <main id="main-content" class="flex-1">
        {{ $slot }}
    </main>

    <footer class="border-t border-stone-200 bg-white">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-5 py-7 sm:px-8">
            <a href="{{ route('home') }}" class="font-bold text-[#174937]">Servio</a>
            <a href="{{ route('services.index') }}" class="text-sm text-stone-700 underline underline-offset-4">Browse services</a>
        </div>
    </footer>
</body>
</html>
