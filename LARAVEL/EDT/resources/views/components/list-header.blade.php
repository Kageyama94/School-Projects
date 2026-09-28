@props(['title', 'createRoute', 'createLabel'])

<div class="flex items-center justify-between mb-4">
    <h3 class="font-semibold text-gray-700 dark:text-gray-200">{{ $title }}</h3>
    <a href="{{ $createRoute }}" class="text-sm underline text-gray-600 dark:text-gray-400">{{ $createLabel }}</a>
</div>
