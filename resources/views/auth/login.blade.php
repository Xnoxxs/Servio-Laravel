<x-auth-layout title="Log in">
    <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
        @csrf
        <x-auth-field name="email" label="Email address" type="email" autocomplete="email" />
        <x-auth-field name="password" label="Password" type="password" autocomplete="current-password" />
        <button type="submit" class="w-full cursor-pointer rounded-lg bg-teal-800 px-4 py-2.5 font-semibold text-white hover:bg-teal-900">
            Log in
        </button>
    </form>
    <p class="mt-5 text-sm text-stone-600">
        New to Servio? <a href="{{ route('signIn') }}" class="font-semibold text-teal-800 underline">Create an account</a>
    </p>
</x-auth-layout>
