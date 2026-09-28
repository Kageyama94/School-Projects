<x-page :title="$title">
    <x-slot name="actions">
        <div class="flex items-center gap-4">
            <a href="{{ $back }}" class="text-sm underline text-gray-600 dark:text-gray-400">&larr; Retour à la liste</a>
        </div>
    </x-slot>

    <x-weekly-schedule :lessons="$lessons" deletable />
</x-page>
