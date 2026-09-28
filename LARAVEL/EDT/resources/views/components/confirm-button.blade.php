@props(['action', 'confirm', 'method' => 'post', 'danger' => false])

<form method="POST" action="{{ $action }}" onsubmit="return confirm(@js($confirm))" class="inline">
    @csrf
    @if ($method !== 'post')
        @method($method)
    @endif
    <button type="submit" {{ $attributes->class([
        'text-xs underline',
        'text-gray-500 dark:text-gray-400' => ! $danger,
        'text-red-600 dark:text-red-400' => $danger,
    ]) }}>{{ $slot }}</button>
</form>
