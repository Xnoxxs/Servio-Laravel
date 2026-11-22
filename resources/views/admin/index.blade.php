<x-auth-layout title="Administration">
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="font-semibold text-teal-800 underline">Log out</button>
    </form>
</x-auth-layout>
