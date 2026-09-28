@props(['action', 'search' => '', 'placeholder' => 'Rechercher'])

<form method="GET" action="{{ $action }}" class="flex items-center gap-2 mb-4">
    <x-text-input name="search" type="text" class="block w-64 text-sm" :value="$search" :placeholder="$placeholder" {{ $attributes }} />
    <x-secondary-button type="submit">Rechercher</x-secondary-button>
    @if ($search !== '')
        <a href="{{ $action }}" class="text-sm underline text-gray-500 dark:text-gray-400">Effacer</a>
    @endif
</form>
