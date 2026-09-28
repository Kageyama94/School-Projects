@props(['title', 'width' => 'max-w-7xl'])

<x-app-layout :title="$title">
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ $title }}
            </h2>
            {{ $actions ?? '' }}
        </div>
    </x-slot>

    <div class="py-12">
        <div class="{{ $width }} mx-auto sm:px-6 lg:px-8 space-y-6">
            {{ $slot }}
        </div>
    </div>
</x-app-layout>
