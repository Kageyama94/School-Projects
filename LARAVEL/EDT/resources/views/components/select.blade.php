@props(['compact' => false])

<select {{ $attributes->class([
    'border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm',
    'mt-1 block w-full' => ! $compact,
    'text-sm' => $compact,
]) }}>
    {{ $slot }}
</select>
