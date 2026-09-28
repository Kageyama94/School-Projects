@props(['type' => 'success'])

<div {{ $attributes->class([
    'p-4 rounded-md text-sm',
    'bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-400' => $type === 'success',
    'bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-400' => $type === 'error',
]) }}>
    {{ $slot }}
</div>
