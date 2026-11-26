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
    <header id="page-top" class="border-b border-stone-200 bg-white">
        <nav aria-label="Main navigation" class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-x-6 gap-y-3 px-5 py-4 sm:px-8">
            <a href="{{ route('home') }}" class="text-2xl font-bold tracking-tight text-[#174937]">Servio</a>
            <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm font-semibold">
                <a href="{{ route('services.index') }}" class="hover:text-emerald-800 hover:underline">Browse services</a>
                @auth
                    <a href="{{ route('bookings.index') }}" class="hover:text-emerald-800 hover:underline">My bookings</a>
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
                    <x-button :href="route('signIn')">Sign up</x-button>
                @endauth
            </div>
        </nav>
    </header>

    <main id="main-content" class="flex-1">
        {{ $slot }}
    </main>

    <footer class="bg-linear-to-br from-[#174937] to-[#0d2c22] text-white">
        <div class="mx-auto max-w-7xl px-5 sm:px-8">
            <div class="grid gap-x-10 gap-y-9 py-10 sm:grid-cols-2 sm:py-14 lg:grid-cols-[2fr_1fr_1fr]">
                <div class="sm:col-span-2 lg:col-span-1">
                    <a href="{{ route('home') }}" class="inline-block text-4xl font-bold tracking-tight hover:text-[#e5f6a3] sm:text-5xl">Servio</a>
                    <span aria-hidden="true" class="mt-4 block h-1 w-12 rounded-full bg-[#e5f6a3]"></span>
                    <p class="mt-4 max-w-xs leading-relaxed text-emerald-50/85">Find local help for everything from home cleaning to moving day.</p>
                    <a href="{{ route('services.index') }}" class="mt-6 inline-flex items-center gap-3 rounded-full bg-[#e5f6a3] px-5 py-2.5 text-sm font-bold text-[#174937] hover:bg-white focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-white">
                        Explore services <span aria-hidden="true">→</span>
                    </a>
                </div>

                <nav aria-label="Footer marketplace" class="space-y-3 text-sm">
                    <h2 class="font-semibold text-[#e5f6a3]">Marketplace</h2>
                    <ul class="space-y-2.5 text-emerald-50">
                        <li><a href="{{ route('home') }}" class="underline-offset-4 hover:underline">Home</a></li>
                        <li><a href="{{ route('services.index') }}" class="underline-offset-4 hover:underline">Search services</a></li>
                        <li><a href="{{ route('home') }}#categories" class="underline-offset-4 hover:underline">Browse categories</a></li>
                    </ul>
                </nav>

                <nav aria-label="Footer account" class="space-y-3 text-sm">
                    <h2 class="font-semibold text-[#e5f6a3]">Account</h2>
                    <ul class="space-y-2.5 text-emerald-50">
                        @auth
                            <li><a href="{{ route('provider.dashboard') }}" class="underline-offset-4 hover:underline">Provider dashboard</a></li>
                            <li><a href="{{ route('provider.services.create') }}" class="underline-offset-4 hover:underline">List a service</a></li>
                        @else
                            <li><a href="{{ route('login') }}" class="underline-offset-4 hover:underline">Log in</a></li>
                            <li><a href="{{ route('signIn') }}" class="underline-offset-4 hover:underline">Create an account</a></li>
                        @endauth
                    </ul>
                </nav>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-white/20 py-5 text-sm text-emerald-50/85">
                <p>© {{ now()->year }} Servio</p>
                <a href="#page-top" class="inline-flex items-center gap-2 font-semibold underline-offset-4 hover:text-white hover:underline">
                    Back to top <span aria-hidden="true">↑</span>
                </a>
            </div>
        </div>
    </footer>
</body>
</html>
