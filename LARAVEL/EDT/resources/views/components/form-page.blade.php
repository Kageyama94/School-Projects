@props(['title', 'action', 'method' => 'post', 'submit' => 'Enregistrer', 'width' => 'max-w-2xl', 'blocked' => false])

<x-page :title="$title" :width="$width">
    <x-card>
        @if ($blocked)
            <div class="text-sm text-gray-600 dark:text-gray-400">{{ $blockedMessage }}</div>
        @else
            <form method="POST" action="{{ $action }}" {{ $attributes->class('space-y-4') }}>
                @csrf
                @if ($method !== 'post')
                    @method($method)
                @endif

                {{ $slot }}

                <div class="flex justify-end">
                    <x-primary-button>{{ $submit }}</x-primary-button>
                </div>
            </form>
        @endif
    </x-card>

    {{ $after ?? '' }}
</x-page>
