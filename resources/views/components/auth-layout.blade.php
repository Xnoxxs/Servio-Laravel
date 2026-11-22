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
<body class="min-h-screen bg-stone-100 px-4 py-10 text-stone-900 antialiased sm:py-16">
    <main class="mx-auto max-w-md">
        <a href="{{ route('home') }}" class="text-lg font-bold tracking-tight text-teal-800">Servio</a>
        <section class="mt-5 rounded-2xl border border-stone-200 bg-white p-6 shadow-sm sm:p-8">
            <h1 class="mb-6 text-2xl font-semibold tracking-tight">{{ $title }}</h1>
            {{ $slot }}
        </section>
    </main>
</body>
</html>
