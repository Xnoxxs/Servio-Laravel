@props(['action'])

<form method="POST" action="{{ $action }}">
    @csrf
    @method('PATCH')
    <x-button>{{ $slot }}</x-button>
</form>
