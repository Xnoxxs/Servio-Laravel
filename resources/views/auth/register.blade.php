<x-auth-layout title="Create an account">
    <form method="POST" action="{{ route('signIn.store') }}" class="space-y-4">
        @csrf
        <x-auth-field name="name" label="Name" autocomplete="name" />
        <x-auth-field name="email" label="Email address" type="email" autocomplete="email" />
        <x-auth-field name="password" label="Password" type="password" autocomplete="new-password" />
        <x-auth-field name="password_confirmation" label="Confirm password" type="password" autocomplete="new-password" />
        <button type="submit" class="w-full cursor-pointer rounded-lg bg-teal-800 px-4 py-2.5 font-semibold text-white hover:bg-teal-900">
            Create account
        </button>
    </form>
    <p class="mt-5 text-sm text-stone-600">
        Already have an account? <a href="{{ route('login') }}" class="font-semibold text-teal-800 underline">Log in</a>
    </p>
</x-auth-layout>
